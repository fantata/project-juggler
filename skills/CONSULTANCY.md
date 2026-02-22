# Fantata Consultancy — Business Rules

## Pricing
- Web builds: £1,500 per build
- Hosting: £350/year per client
- Day rate (when applicable): target £600-£1,500 depending on complexity
- Retainer clients: monthly or yearly recurring

## Client Workflow
1. Enquiry comes in (often via email) → create_issue with raw_email parsing
2. Quote/scope → update project with money_value and money_status=speculative
3. Accepted → money_status=awaiting, status=active
4. Work → log_work entries, issue tracking
5. Deliver → waiting_on_client=true
6. Invoice → money_status=awaiting
7. Paid → money_status=paid

## When Creating Client Projects
- Always set type=client, category=consultancy
- Set money_value even if estimated
- Set a deadline if one exists
- Set next_action immediately — what's the very first thing to do?

## Tech Stack Context
- Chris works in TALL stack (Tailwind, Alpine, Laravel, Livewire)
- Deploys via CapRover
- Uses GitHub for version control
- If project has a repo, set github_repo for issue sync
