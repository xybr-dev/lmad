<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use Lmad\Mcp\LmadServer;

// Local MCP server for LMAD API Discovery
Mcp::local('lmad', LmadServer::class);
