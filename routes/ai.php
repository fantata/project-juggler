<?php

use App\Mcp\Servers\Juggler;
use Laravel\Mcp\Facades\Mcp;

// HTTP transport — guarded by Sanctum personal access tokens.
// Mint a token from the Profile page; clients send `Authorization: Bearer <token>`.
Mcp::web('/mcp', Juggler::class)
    ->middleware('auth:sanctum');

// stdio transport for Claude Desktop:
//   claude mcp add juggler -- php /Users/chris/Code/Projects/project-juggler/artisan mcp:start juggler
Mcp::local('juggler', Juggler::class);
