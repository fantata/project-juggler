#!/usr/bin/env node

const readline = require('readline');
const mysql = require('mysql2/promise');

let db;

async function getDb() {
    if (!db) {
        db = await mysql.createConnection({
            host: process.env.DB_HOST || '127.0.0.1',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_DATABASE || 'project_juggler',
        });
    }
    return db;
}

async function parseEmail(rawEmail) {
    const apiKey = process.env.GROQ_API_KEY;

    if (!apiKey) {
        return fallbackParse(rawEmail);
    }

    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`,
            },
            body: JSON.stringify({
                model: 'llama-3.3-70b-versatile',
                max_tokens: 1024,
                messages: [
                    {
                        role: 'user',
                        content: `Extract structured data from this client email. Return ONLY valid JSON (no markdown, no code fences) with these fields:\n- title: a short summary of what the client needs (max 100 chars)\n- description: bullet-point action items extracted from the email\n- urgency: one of 'low', 'medium', 'high' based on the tone and content\n\nEmail:\n${rawEmail}`,
                    },
                ],
            }),
        });

        const data = await response.json();
        let text = data.choices?.[0]?.message?.content || '';

        // Strip markdown code fences if present
        text = text.replace(/^```(?:json)?\s*/i, '').replace(/\s*```$/i, '').trim();

        const parsed = JSON.parse(text);

        if (!parsed || !parsed.title) {
            return fallbackParse(rawEmail);
        }

        // Handle description as array (bullet points) or string
        let description = parsed.description || null;
        if (Array.isArray(description)) {
            description = description.map(item => `• ${item}`).join('\n');
        }

        return {
            title: parsed.title.substring(0, 255),
            description,
            urgency: ['low', 'medium', 'high'].includes(parsed.urgency) ? parsed.urgency : 'medium',
        };
    } catch (e) {
        return fallbackParse(rawEmail);
    }
}

// GitHub API helpers
function githubConfigured() {
    return !!process.env.GITHUB_TOKEN;
}

async function githubApi(method, path, body = null) {
    const opts = {
        method,
        headers: {
            'Authorization': `Bearer ${process.env.GITHUB_TOKEN}`,
            'Accept': 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version': '2022-11-28',
            'Content-Type': 'application/json',
        },
    };
    if (body) opts.body = JSON.stringify(body);
    const response = await fetch(`https://api.github.com${path}`, opts);
    return response.json();
}

async function githubCreateIssue(repo, title, body) {
    return githubApi('POST', `/repos/${repo}/issues`, { title, body });
}

async function githubCloseIssue(repo, number) {
    return githubApi('PATCH', `/repos/${repo}/issues/${number}`, { state: 'closed' });
}

async function githubReopenIssue(repo, number) {
    return githubApi('PATCH', `/repos/${repo}/issues/${number}`, { state: 'open' });
}

async function githubListIssues(repo, state = 'all') {
    const allIssues = [];
    let page = 1;
    while (true) {
        const issues = await githubApi('GET', `/repos/${repo}/issues?state=${state}&per_page=100&page=${page}`);
        if (!Array.isArray(issues) || issues.length === 0) break;
        // Filter out pull requests
        allIssues.push(...issues.filter(i => !i.pull_request));
        if (issues.length < 100) break;
        page++;
    }
    return allIssues;
}

function fallbackParse(rawEmail) {
    const lines = rawEmail.trim().split('\n');
    return {
        title: (lines[0] || 'Untitled issue').substring(0, 255),
        description: rawEmail,
        urgency: 'medium',
    };
}

const tools = [
    {
        name: 'list_projects',
        description: 'List all projects with optional filters',
        inputSchema: {
            type: 'object',
            properties: {
                type: { type: 'string', description: 'Filter by type: client, personal, speculative', enum: ['client', 'personal', 'speculative'] },
                status: { type: 'string', description: 'Filter by status: active, paused, blocked, complete, killed', enum: ['active', 'paused', 'blocked', 'complete', 'killed'] },
                money_status: { type: 'string', description: 'Filter by money status: paid, partial, awaiting, none, speculative', enum: ['paid', 'partial', 'awaiting', 'none', 'speculative'] },
                waiting_on_client: { type: 'boolean', description: 'Filter by waiting on client status' },
            },
        },
    },
    {
        name: 'get_project',
        description: 'Get detailed information about a project by ID or name (fuzzy match)',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'integer', description: 'Project ID' },
                name: { type: 'string', description: 'Project name (fuzzy match)' },
            },
        },
    },
    {
        name: 'create_project',
        description: 'Create a new project',
        inputSchema: {
            type: 'object',
            properties: {
                name: { type: 'string', description: 'Project name' },
                type: { type: 'string', description: 'Project type', enum: ['client', 'personal', 'speculative'] },
                status: { type: 'string', description: 'Project status', enum: ['active', 'paused', 'blocked', 'complete', 'killed'] },
                waiting_on_client: { type: 'boolean', description: 'Waiting on client response' },
                priority: { type: 'integer', description: 'Priority (lower = higher priority)' },
                money_status: { type: 'string', description: 'Money status', enum: ['paid', 'partial', 'awaiting', 'none', 'speculative'] },
                money_value: { type: 'number', description: 'Money value in GBP' },
                deadline: { type: 'string', description: 'Deadline (YYYY-MM-DD)' },
                next_action: { type: 'string', description: 'GTD-style next action' },
                notes: { type: 'string', description: 'Project notes' },
                github_repo: { type: 'string', description: 'GitHub repository (org/repo format)' },
            },
            required: ['name', 'type'],
        },
    },
    {
        name: 'update_project',
        description: 'Update an existing project (partial updates allowed)',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'integer', description: 'Project ID' },
                name: { type: 'string', description: 'Project name (for lookup if ID not provided)' },
                new_name: { type: 'string', description: 'New project name' },
                type: { type: 'string', enum: ['client', 'personal', 'speculative'] },
                status: { type: 'string', enum: ['active', 'paused', 'blocked', 'complete', 'killed'] },
                waiting_on_client: { type: 'boolean', description: 'Waiting on client response' },
                priority: { type: 'integer', description: 'Priority (lower = higher priority)' },
                money_status: { type: 'string', enum: ['paid', 'partial', 'awaiting', 'none', 'speculative'] },
                money_value: { type: 'number' },
                deadline: { type: 'string', description: 'YYYY-MM-DD or empty to clear' },
                next_action: { type: 'string', description: 'Empty to clear' },
                notes: { type: 'string' },
                github_repo: { type: 'string', description: 'GitHub repo (org/repo format, empty to clear)' },
            },
        },
    },
    {
        name: 'log_work',
        description: 'Add a log entry to a project',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'integer', description: 'Project ID' },
                project_name: { type: 'string', description: 'Project name (fuzzy match)' },
                entry: { type: 'string', description: 'Log entry text' },
            },
            required: ['entry'],
        },
    },
    {
        name: 'quick_status',
        description: 'Get overview: active count, blocked projects, upcoming deadlines, projects awaiting money, open issues',
        inputSchema: { type: 'object', properties: {} },
    },
    {
        name: 'create_issue',
        description: 'Create an issue/ticket on a project. Either provide a title directly, or provide raw_email to have AI parse it into a structured issue.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'integer', description: 'Project ID' },
                project_name: { type: 'string', description: 'Project name (fuzzy match)' },
                title: { type: 'string', description: 'Issue title (optional if raw_email provided)' },
                description: { type: 'string', description: 'Issue description / action items' },
                urgency: { type: 'string', description: 'Issue urgency', enum: ['low', 'medium', 'high'] },
                raw_email: { type: 'string', description: 'Raw client email text. If provided without a title, AI will parse it.' },
                tasks: { type: 'array', description: 'Array of task descriptions to create as sub-tasks on the issue', items: { type: 'string' } },
            },
        },
    },
    {
        name: 'list_issues',
        description: 'List issues for a project with optional status filter',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'integer', description: 'Project ID' },
                project_name: { type: 'string', description: 'Project name (fuzzy match)' },
                status: { type: 'string', description: 'Filter by issue status', enum: ['open', 'in_progress', 'done'] },
            },
        },
    },
    {
        name: 'update_issue',
        description: 'Update an issue status, title, description, or urgency',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'integer', description: 'Issue ID' },
                title: { type: 'string', description: 'New issue title' },
                description: { type: 'string', description: 'New issue description' },
                status: { type: 'string', description: 'New issue status', enum: ['open', 'in_progress', 'done'] },
                urgency: { type: 'string', description: 'New issue urgency', enum: ['low', 'medium', 'high'] },
            },
            required: ['id'],
        },
    },
    {
        name: 'sync_issues',
        description: 'Sync issues with GitHub for a project that has a github_repo configured. Pulls new/updated issues from GitHub.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'integer', description: 'Project ID' },
                project_name: { type: 'string', description: 'Project name (fuzzy match)' },
            },
        },
    },
    {
        name: 'list_tasks',
        description: 'List all actionable work items across all active projects. Returns both standalone issues (issues with no sub-tasks) and child tasks from issues that have sub-tasks. Use this for a cross-project view of everything that needs doing.',
        inputSchema: {
            type: 'object',
            properties: {
                include_completed: { type: 'boolean', description: 'Include completed items (default false)' },
            },
        },
    },
];

async function findProject(args) {
    const conn = await getDb();
    if (args.id) {
        const [rows] = await conn.execute('SELECT * FROM projects WHERE id = ?', [args.id]);
        return rows[0] || null;
    }
    if (args.name) {
        const [rows] = await conn.execute('SELECT * FROM projects WHERE name LIKE ? LIMIT 1', [`%${args.name}%`]);
        return rows[0] || null;
    }
    return null;
}

async function handleToolCall(name, args) {
    const conn = await getDb();

    switch (name) {
        case 'list_projects': {
            let sql = `SELECT p.*, (SELECT COUNT(*) FROM issues WHERE issues.project_id = p.id AND issues.status IN ('open', 'in_progress')) as open_issue_count FROM projects p WHERE 1=1`;
            const params = [];
            if (args.type) { sql += ' AND p.type = ?'; params.push(args.type); }
            if (args.status) { sql += ' AND p.status = ?'; params.push(args.status); }
            if (args.money_status) { sql += ' AND p.money_status = ?'; params.push(args.money_status); }
            if (args.waiting_on_client !== undefined) { sql += ' AND p.waiting_on_client = ?'; params.push(args.waiting_on_client ? 1 : 0); }
            sql += ' ORDER BY CASE WHEN p.priority IS NULL THEN 1 ELSE 0 END, p.priority ASC, CASE WHEN p.money_status = "awaiting" THEN 0 ELSE 1 END, p.deadline ASC, p.money_value DESC, p.last_touched_at DESC';
            const [rows] = await conn.execute(sql, params);
            return {
                count: rows.length,
                projects: rows.map(p => ({
                    id: p.id,
                    name: p.name,
                    type: p.type,
                    status: p.status,
                    waiting_on_client: !!p.waiting_on_client,
                    priority: p.priority,
                    money_status: p.money_status,
                    money_value: p.money_value,
                    deadline: p.deadline ? p.deadline.toISOString().split('T')[0] : null,
                    next_action: p.next_action,
                    github_repo: p.github_repo,
                    open_issue_count: p.open_issue_count,
                    last_touched: p.last_touched_at,
                })),
            };
        }

        case 'get_project': {
            const project = await findProject(args);
            if (!project) return { error: 'Project not found' };
            const [logs] = await conn.execute(
                'SELECT entry, created_at FROM project_logs WHERE project_id = ? ORDER BY created_at DESC LIMIT 10',
                [project.id]
            );
            const [[{ open_issue_count }]] = await conn.execute(
                "SELECT COUNT(*) as open_issue_count FROM issues WHERE project_id = ? AND status IN ('open', 'in_progress')",
                [project.id]
            );
            const [issues] = await conn.execute(
                "SELECT id, title, status, urgency, github_issue_number, created_at FROM issues WHERE project_id = ? AND status IN ('open', 'in_progress') ORDER BY created_at DESC LIMIT 10",
                [project.id]
            );
            return {
                ...project,
                deadline: project.deadline ? project.deadline.toISOString().split('T')[0] : null,
                github_repo: project.github_repo,
                open_issue_count,
                recent_logs: logs.map(l => ({ entry: l.entry, created_at: l.created_at })),
                open_issues: issues.map(i => ({ id: i.id, title: i.title, status: i.status, urgency: i.urgency, github_issue_number: i.github_issue_number, created_at: i.created_at })),
            };
        }

        case 'create_project': {
            if (!args.name) return { error: 'Name is required' };
            if (!args.type) return { error: 'Type is required' };
            const [result] = await conn.execute(
                `INSERT INTO projects (name, type, status, waiting_on_client, priority, money_status, money_value, deadline, next_action, notes, github_repo, last_touched_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())`,
                [args.name, args.type, args.status || 'active', args.waiting_on_client ? 1 : 0, args.priority || 0, args.money_status || 'none', args.money_value || null, args.deadline || null, args.next_action || null, args.notes || null, args.github_repo || null]
            );
            return { success: true, message: `Project '${args.name}' created`, id: result.insertId };
        }

        case 'update_project': {
            const project = await findProject(args);
            if (!project) return { error: 'Project not found' };
            const updates = [];
            const params = [];
            if (args.new_name !== undefined) { updates.push('name = ?'); params.push(args.new_name); }
            if (args.type !== undefined) { updates.push('type = ?'); params.push(args.type); }
            if (args.status !== undefined) { updates.push('status = ?'); params.push(args.status); }
            if (args.waiting_on_client !== undefined) { updates.push('waiting_on_client = ?'); params.push(args.waiting_on_client ? 1 : 0); }
            if (args.priority !== undefined) { updates.push('priority = ?'); params.push(args.priority); }
            if (args.money_status !== undefined) { updates.push('money_status = ?'); params.push(args.money_status); }
            if (args.money_value !== undefined) { updates.push('money_value = ?'); params.push(args.money_value); }
            if (args.deadline !== undefined) { updates.push('deadline = ?'); params.push(args.deadline || null); }
            if (args.next_action !== undefined) { updates.push('next_action = ?'); params.push(args.next_action || null); }
            if (args.notes !== undefined) { updates.push('notes = ?'); params.push(args.notes); }
            if (args.github_repo !== undefined) { updates.push('github_repo = ?'); params.push(args.github_repo || null); }
            updates.push('last_touched_at = NOW()');
            updates.push('updated_at = NOW()');
            params.push(project.id);
            await conn.execute(`UPDATE projects SET ${updates.join(', ')} WHERE id = ?`, params);
            return { success: true, message: `Project '${project.name}' updated`, id: project.id };
        }

        case 'log_work': {
            if (!args.entry) return { error: 'Entry text is required' };
            const project = await findProject({ id: args.project_id, name: args.project_name });
            if (!project) return { error: 'Project not found' };
            await conn.execute(
                'INSERT INTO project_logs (project_id, entry, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                [project.id, args.entry]
            );
            await conn.execute('UPDATE projects SET last_touched_at = NOW(), updated_at = NOW() WHERE id = ?', [project.id]);
            return { success: true, message: `Logged work on '${project.name}'`, project_id: project.id };
        }

        case 'quick_status': {
            const [[{ active_count }]] = await conn.execute("SELECT COUNT(*) as active_count FROM projects WHERE status = 'active'");
            const [blocked] = await conn.execute("SELECT id, name, next_action FROM projects WHERE status = 'blocked'");
            const [deadlines] = await conn.execute(
                "SELECT id, name, deadline FROM projects WHERE deadline IS NOT NULL AND deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND deadline >= CURDATE() AND status NOT IN ('complete', 'killed') ORDER BY deadline"
            );
            const [awaiting] = await conn.execute(
                "SELECT id, name, money_value FROM projects WHERE money_status = 'awaiting' AND status NOT IN ('complete', 'killed')"
            );
            const [[{ total_awaiting }]] = await conn.execute(
                "SELECT COALESCE(SUM(money_value), 0) as total_awaiting FROM projects WHERE money_status = 'awaiting' AND status NOT IN ('complete', 'killed')"
            );
            const [[{ open_issue_count }]] = await conn.execute(
                "SELECT COUNT(*) as open_issue_count FROM issues WHERE status IN ('open', 'in_progress')"
            );
            return {
                active_projects: active_count,
                blocked_projects: { count: blocked.length, projects: blocked },
                upcoming_deadlines: { count: deadlines.length, projects: deadlines.map(p => ({ ...p, deadline: p.deadline?.toISOString().split('T')[0] })) },
                awaiting_money: { count: awaiting.length, total_value: total_awaiting, projects: awaiting },
                open_issues: open_issue_count,
            };
        }

        case 'create_issue': {
            const project = await findProject({ id: args.project_id, name: args.project_name });
            if (!project) return { error: 'Project not found' };

            let title = args.title || null;
            let description = args.description || null;
            let urgency = args.urgency || 'medium';

            if (args.raw_email && !title) {
                const parsed = await parseEmail(args.raw_email);
                title = parsed.title;
                description = description || parsed.description;
                urgency = parsed.urgency;
            }

            if (!title) return { error: 'Title is required (or provide raw_email for AI parsing)' };

            const [result] = await conn.execute(
                'INSERT INTO issues (project_id, title, description, status, urgency, raw_email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [project.id, title, description, 'open', urgency, args.raw_email || null]
            );

            // Create tasks if provided
            const tasks = args.tasks && args.tasks.length > 0 ? args.tasks : [];
            for (let i = 0; i < tasks.length; i++) {
                const taskDesc = (tasks[i] || '').trim();
                if (taskDesc) {
                    await conn.execute(
                        'INSERT INTO issue_tasks (issue_id, description, is_complete, position, is_ai_generated, created_at, updated_at) VALUES (?, ?, 0, ?, 0, NOW(), NOW())',
                        [result.insertId, taskDesc, i + 1]
                    );
                }
            }

            // Push to GitHub if configured
            let githubNumber = null;
            if (project.github_repo && githubConfigured()) {
                try {
                    const ghIssue = await githubCreateIssue(project.github_repo, title, description);
                    if (ghIssue && ghIssue.number) {
                        await conn.execute('UPDATE issues SET github_issue_number = ? WHERE id = ?', [ghIssue.number, result.insertId]);
                        githubNumber = ghIssue.number;
                    }
                } catch (e) { /* GitHub push failed — issue still created locally */ }
            }

            await conn.execute('UPDATE projects SET last_touched_at = NOW(), updated_at = NOW() WHERE id = ?', [project.id]);

            return { success: true, message: `Issue created on '${project.name}'${githubNumber ? ` (GitHub #${githubNumber})` : ''}`, issue_id: result.insertId, title, urgency, github_issue_number: githubNumber };
        }

        case 'list_issues': {
            const project = await findProject({ id: args.project_id, name: args.project_name });
            if (!project) return { error: 'Project not found' };

            let sql = 'SELECT id, title, description, status, urgency, github_issue_number, created_at FROM issues WHERE project_id = ?';
            const params = [project.id];
            if (args.status) { sql += ' AND status = ?'; params.push(args.status); }
            sql += ' ORDER BY created_at DESC';

            const [issues] = await conn.execute(sql, params);
            return {
                project: project.name,
                count: issues.length,
                issues: issues.map(i => ({ id: i.id, title: i.title, description: i.description, status: i.status, urgency: i.urgency, github_issue_number: i.github_issue_number, created_at: i.created_at })),
            };
        }

        case 'update_issue': {
            if (!args.id) return { error: 'Issue ID is required' };
            const [issueRows] = await conn.execute('SELECT * FROM issues WHERE id = ?', [args.id]);
            if (!issueRows[0]) return { error: 'Issue not found' };
            const issue = issueRows[0];

            const updates = [];
            const params = [];
            if (args.title !== undefined) { updates.push('title = ?'); params.push(args.title); }
            if (args.description !== undefined) { updates.push('description = ?'); params.push(args.description); }
            if (args.status !== undefined) { updates.push('status = ?'); params.push(args.status); }
            if (args.urgency !== undefined) { updates.push('urgency = ?'); params.push(args.urgency); }
            updates.push('updated_at = NOW()');
            params.push(issue.id);

            const oldStatus = issue.status;
            await conn.execute(`UPDATE issues SET ${updates.join(', ')} WHERE id = ?`, params);
            await conn.execute('UPDATE projects SET last_touched_at = NOW(), updated_at = NOW() WHERE id = ?', [issue.project_id]);

            // Push status change to GitHub
            if (args.status && issue.github_issue_number) {
                const [projRows] = await conn.execute('SELECT github_repo FROM projects WHERE id = ?', [issue.project_id]);
                const repo = projRows[0]?.github_repo;
                if (repo && githubConfigured()) {
                    try {
                        if (args.status === 'done' && oldStatus !== 'done') {
                            await githubCloseIssue(repo, issue.github_issue_number);
                        } else if (args.status !== 'done' && oldStatus === 'done') {
                            await githubReopenIssue(repo, issue.github_issue_number);
                        }
                    } catch (e) { /* GitHub sync failed silently */ }
                }
            }

            return { success: true, message: `Issue '${issue.title}' updated`, id: issue.id };
        }

        case 'sync_issues': {
            const project = await findProject({ id: args.project_id, name: args.project_name });
            if (!project) return { error: 'Project not found' };
            if (!project.github_repo) return { error: 'Project has no GitHub repo configured' };
            if (!githubConfigured()) return { error: 'GITHUB_TOKEN not configured' };

            let ghIssues;
            try {
                ghIssues = await githubListIssues(project.github_repo);
            } catch (e) {
                return { error: 'Failed to fetch GitHub issues: ' + e.message };
            }

            let created = 0, updated = 0;

            for (const ghIssue of ghIssues) {
                const number = ghIssue.number;
                const [existing] = await conn.execute(
                    'SELECT * FROM issues WHERE project_id = ? AND github_issue_number = ?',
                    [project.id, number]
                );
                const ghStatus = ghIssue.state === 'closed' ? 'done' : 'open';

                if (existing[0]) {
                    const local = existing[0];
                    const updates = [];
                    const params = [];
                    if (local.title !== ghIssue.title) {
                        updates.push('title = ?'); params.push(ghIssue.title);
                        updated++;
                    }
                    if (ghStatus === 'done' && local.status !== 'done') {
                        updates.push('status = ?'); params.push('done');
                        updated++;
                    } else if (ghStatus === 'open' && local.status === 'done') {
                        updates.push('status = ?'); params.push('open');
                        updated++;
                    }
                    if (updates.length > 0) {
                        updates.push('updated_at = NOW()');
                        params.push(local.id);
                        await conn.execute(`UPDATE issues SET ${updates.join(', ')} WHERE id = ?`, params);
                    }
                } else {
                    await conn.execute(
                        'INSERT INTO issues (project_id, title, description, status, urgency, github_issue_number, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                        [project.id, ghIssue.title, ghIssue.body || null, ghStatus, 'medium', number]
                    );
                    created++;
                }
            }

            return { success: true, message: `Synced issues for '${project.name}': ${created} created, ${updated} updated`, created, updated, total_github_issues: ghIssues.length };
        }

        case 'list_tasks': {
            const includeCompleted = !!args.include_completed;

            // Child tasks from issues that have sub-tasks, on active projects
            let taskSql = `
                SELECT it.id, it.description, it.is_complete, it.is_ai_generated,
                       i.title as issue_title, i.id as issue_id, i.urgency,
                       p.name as project_name, p.id as project_id
                FROM issue_tasks it
                JOIN issues i ON it.issue_id = i.id
                JOIN projects p ON i.project_id = p.id
                WHERE p.status NOT IN ('complete', 'killed')
            `;
            if (!includeCompleted) {
                taskSql += ' AND it.is_complete = 0';
            }
            taskSql += ' ORDER BY it.created_at DESC';

            const [childTasks] = await conn.execute(taskSql);

            // Standalone issues (no child tasks) on active projects
            let issueSql = `
                SELECT i.id, i.title, i.status, i.urgency,
                       p.name as project_name, p.id as project_id
                FROM issues i
                JOIN projects p ON i.project_id = p.id
                WHERE p.status NOT IN ('complete', 'killed')
                  AND NOT EXISTS (SELECT 1 FROM issue_tasks WHERE issue_id = i.id)
            `;
            if (!includeCompleted) {
                issueSql += " AND i.status IN ('open', 'in_progress')";
            }
            issueSql += ' ORDER BY i.created_at DESC';

            const [standaloneIssues] = await conn.execute(issueSql);

            const items = [];

            for (const t of childTasks) {
                items.push({
                    type: 'task',
                    id: t.id,
                    description: t.description,
                    is_complete: !!t.is_complete,
                    project: t.project_name,
                    project_id: t.project_id,
                    parent_issue: t.issue_title,
                    parent_issue_id: t.issue_id,
                    urgency: t.urgency,
                });
            }

            for (const i of standaloneIssues) {
                items.push({
                    type: 'issue',
                    id: i.id,
                    description: i.title,
                    is_complete: i.status === 'done',
                    status: i.status,
                    project: i.project_name,
                    project_id: i.project_id,
                    urgency: i.urgency,
                });
            }

            return { count: items.length, items };
        }

        default:
            return { error: `Unknown tool: ${name}` };
    }
}

function handleRequest(request) {
    const { method, id, params } = request;

    switch (method) {
        case 'initialize':
            return {
                jsonrpc: '2.0',
                id,
                result: {
                    protocolVersion: '2024-11-05',
                    capabilities: { tools: {} },
                    serverInfo: { name: 'project-juggler', version: '1.0.0' },
                },
            };

        case 'notifications/initialized':
            return null;

        case 'tools/list':
            return { jsonrpc: '2.0', id, result: { tools } };

        case 'tools/call':
            return handleToolCall(params.name, params.arguments || {}).then(result => ({
                jsonrpc: '2.0',
                id,
                result: {
                    content: [{ type: 'text', text: typeof result === 'string' ? result : JSON.stringify(result, null, 2) }],
                },
            }));

        default:
            return { jsonrpc: '2.0', id, error: { code: -32601, message: `Method not found: ${method}` } };
    }
}

const rl = readline.createInterface({ input: process.stdin, output: process.stdout, terminal: false });

rl.on('line', async (line) => {
    if (!line.trim()) return;
    try {
        const request = JSON.parse(line);
        const response = await handleRequest(request);
        if (response) {
            process.stdout.write(JSON.stringify(response) + '\n');
        }
    } catch (e) {
        process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id: null, error: { code: -32700, message: 'Parse error' } }) + '\n');
    }
});

rl.on('close', () => process.exit(0));
