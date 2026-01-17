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
        description: 'Get overview: active count, blocked projects, upcoming deadlines, projects awaiting money',
        inputSchema: { type: 'object', properties: {} },
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
            let sql = 'SELECT * FROM projects WHERE 1=1';
            const params = [];
            if (args.type) { sql += ' AND type = ?'; params.push(args.type); }
            if (args.status) { sql += ' AND status = ?'; params.push(args.status); }
            if (args.money_status) { sql += ' AND money_status = ?'; params.push(args.money_status); }
            if (args.waiting_on_client !== undefined) { sql += ' AND waiting_on_client = ?'; params.push(args.waiting_on_client ? 1 : 0); }
            sql += ' ORDER BY CASE WHEN priority IS NULL THEN 1 ELSE 0 END, priority ASC, CASE WHEN money_status = "awaiting" THEN 0 ELSE 1 END, deadline ASC, money_value DESC, last_touched_at DESC';
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
            return {
                ...project,
                deadline: project.deadline ? project.deadline.toISOString().split('T')[0] : null,
                recent_logs: logs.map(l => ({ entry: l.entry, created_at: l.created_at })),
            };
        }

        case 'create_project': {
            if (!args.name) return { error: 'Name is required' };
            if (!args.type) return { error: 'Type is required' };
            const [result] = await conn.execute(
                `INSERT INTO projects (name, type, status, waiting_on_client, priority, money_status, money_value, deadline, next_action, notes, last_touched_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())`,
                [args.name, args.type, args.status || 'active', args.waiting_on_client ? 1 : 0, args.priority || 0, args.money_status || 'none', args.money_value || null, args.deadline || null, args.next_action || null, args.notes || null]
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
            return {
                active_projects: active_count,
                blocked_projects: { count: blocked.length, projects: blocked },
                upcoming_deadlines: { count: deadlines.length, projects: deadlines.map(p => ({ ...p, deadline: p.deadline?.toISOString().split('T')[0] })) },
                awaiting_money: { count: awaiting.length, total_value: total_awaiting, projects: awaiting },
            };
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
