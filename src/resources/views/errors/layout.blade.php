<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') · CriptoLab</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --accent: #4f46e5;
            --border: #e5e7eb;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111827;
                --card: #1f2937;
                --text: #f9fafb;
                --muted: #9ca3af;
                --accent: #818cf8;
                --border: #374151;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        main {
            width: 100%;
            max-width: 36rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            background: var(--card);
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 25px rgb(0 0 0 / 0.08);
        }
        .code { font-size: 4rem; font-weight: 800; color: var(--accent); line-height: 1; }
        h1 { margin: 1rem 0 0.5rem; font-size: 1.5rem; }
        p { margin: 0; color: var(--muted); line-height: 1.6; }
        a {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.7rem 1.2rem;
            border-radius: 0.5rem;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .brand { margin-top: 1.5rem; font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); }
    </style>
</head>
<body>
    <main>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Volver al inicio</a>
        <div class="brand">CriptoLab · Actividad educativa</div>
    </main>
</body>
</html>
