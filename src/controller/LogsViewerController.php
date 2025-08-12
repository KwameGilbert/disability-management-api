<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogsViewerController
{
    /**
     * Get a list of all available log directories
     */
    public function listLogDirectories(Request $request, Response $response): Response
    {
        $logPath = BASE . 'src/logs/';
        $dirs = array_filter(scandir($logPath), function ($item) use ($logPath) {
            return is_dir($logPath . $item) && !in_array($item, ['.', '..']);
        });

        $data = [
            'status' => 'success',
            'data' => array_values($dirs)
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get a list of log files in a specific directory
     */
    public function listLogFiles(Request $request, Response $response, array $args): Response
    {
        $directory = $args['directory'];

        // Sanitize directory name to prevent directory traversal
        $directory = str_replace(['..', '/', '\\'], '', $directory);

        $logPath = BASE . 'src/logs/' . $directory . '/';

        if (!is_dir($logPath)) {
            $data = [
                'status' => 'error',
                'message' => 'Directory not found'
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $files = array_filter(scandir($logPath), function ($item) use ($logPath) {
            return is_file($logPath . $item) && !in_array($item, ['.', '..']);
        });

        $data = [
            'status' => 'success',
            'data' => array_values($files)
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get the contents of a specific log file
     */
    public function viewLogFile(Request $request, Response $response, array $args): Response
    {
        $directory = $args['directory'];
        $file = $args['file'];

        // Sanitize directory and file names to prevent directory traversal
        $directory = str_replace(['..', '/', '\\'], '', $directory);
        $file = str_replace(['..', '/', '\\'], '', $file);

        $logPath = BASE . 'src/logs/' . $directory . '/' . $file;

        if (!file_exists($logPath)) {
            $data = [
                'status' => 'error',
                'message' => 'Log file not found'
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $contents = file_get_contents($logPath);

        $data = [
            'status' => 'success',
            'data' => [
                'filename' => $file,
                'content' => $contents
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Render the logs viewer interface HTML
     */
    public function renderInterface(Request $request, Response $response): Response
    {
        $html = $this->getInterfaceHTML();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /**
     * Authentication endpoint for log viewer
     */
    public function authenticate(Request $request, Response $response): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Handle both form submissions and JSON requests
        if (strstr($contentType, 'application/json')) {
            $contents = json_decode($request->getBody()->getContents(), true);
            $username = $contents['username'] ?? '';
            $password = $contents['password'] ?? '';
        } else {
            $params = $request->getParsedBody();
            $username = $params['username'] ?? '';
            $password = $params['password'] ?? '';
        }

        // Admin credentials from environment variables or use defaults if not set
        $adminUsername = $_ENV['LOGS_ADMIN_USERNAME'] ?? 'admin';
        $adminPassword = $_ENV['LOGS_ADMIN_PASSWORD'] ?? 'admin123';

        if ($username === $adminUsername && $password === $adminPassword) {
            // Generate a simple token
            $token = bin2hex(random_bytes(32));
            $expiration = time() + 3600; // 1 hour

            $data = [
                'status' => 'success',
                'data' => [
                    'token' => $token,
                    'expires' => $expiration
                ]
            ];

            // In a real application, you should store this token securely
            // For this simple implementation, we'll just return it

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'status' => 'error',
            'message' => 'Invalid credentials'
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    /**
     * Generate the HTML for the logs viewer interface
     */
    private function getInterfaceHTML(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
        }
        .hidden {
            display: none;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Logs Viewer</h1>
        
        <div id="login-form">
            <div class="card">
                <div class="card-header">Login</div>
                <div class="card-body">
                    <div class="alert alert-danger hidden" id="login-error"></div>
                    <form id="auth-form" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="logs-viewer" class="hidden">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">Log Directories</div>
                        <div class="card-body">
                            <ul class="list-group" id="directories-list"></ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <span id="current-path">Select a directory</span>
                                <button id="logout-btn" class="btn btn-sm btn-danger">Logout</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="files-list-container" class="hidden">
                                <h5>Log Files:</h5>
                                <ul class="list-group" id="files-list"></ul>
                            </div>
                            <div id="log-content-container" class="log-container hidden">
                                <h5 id="current-file"></h5>
                                <pre id="log-content"></pre>
                                <button class="btn btn-secondary" id="back-to-files">Back to Files</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('logsToken');
            if (token) {
                showLogsViewer();
                loadDirectories();
            }
            
            document.getElementById('auth-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                fetch('/logs-viewer/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        localStorage.setItem('logsToken', data.data.token);
                        showLogsViewer();
                        loadDirectories();
                    } else {
                        document.getElementById('login-error').textContent = data.message;
                        document.getElementById('login-error').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    document.getElementById('login-error').textContent = 'An error occurred. Please try again.';
                    document.getElementById('login-error').classList.remove('hidden');
                });
            });
            
            document.getElementById('logout-btn').addEventListener('click', function() {
                localStorage.removeItem('logsToken');
                hideLogsViewer();
            });
            
            document.getElementById('back-to-files').addEventListener('click', function() {
                document.getElementById('files-list-container').classList.remove('hidden');
                document.getElementById('log-content-container').classList.add('hidden');
            });
        });
        
        function showLogsViewer() {
            document.getElementById('login-form').classList.add('hidden');
            document.getElementById('logs-viewer').classList.remove('hidden');
        }
        
        function hideLogsViewer() {
            document.getElementById('login-form').classList.remove('hidden');
            document.getElementById('logs-viewer').classList.add('hidden');
        }
        
        function loadDirectories() {
            fetch('/logs-viewer/directories', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('logsToken')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const dirList = document.getElementById('directories-list');
                    dirList.innerHTML = '';
                    
                    data.data.forEach(dir => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.textContent = dir;
                        li.addEventListener('click', () => loadFiles(dir));
                        dirList.appendChild(li);
                    });
                }
            });
        }
        
        function loadFiles(directory) {
            document.getElementById('current-path').textContent = 'Directory: ' + directory;
            
            fetch('/logs-viewer/directories/' + directory + '/files', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('logsToken')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const filesList = document.getElementById('files-list');
                    filesList.innerHTML = '';
                    
                    data.data.forEach(file => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.textContent = file;
                        li.addEventListener('click', () => loadLogContent(directory, file));
                        filesList.appendChild(li);
                    });
                    
                    document.getElementById('files-list-container').classList.remove('hidden');
                    document.getElementById('log-content-container').classList.add('hidden');
                }
            });
        }
        
        function loadLogContent(directory, file) {
            document.getElementById('current-file').textContent = file;
            
            fetch('/logs-viewer/directories/' + directory + '/files/' + file, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('logsToken')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('log-content').textContent = data.data.content;
                    document.getElementById('files-list-container').classList.add('hidden');
                    document.getElementById('log-content-container').classList.remove('hidden');
                }
            });
        }
    </script>
</body>
</html>
HTML;
    }
}
