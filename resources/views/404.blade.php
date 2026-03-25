<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#10243e',
                        mist: '#eef6ff',
                        accent: '#0f766e',
                        warn: '#dc2626'
                    },
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,_#eef6ff,_#ffffff_58%)] text-ink">
    <main class="mx-auto flex min-h-screen max-w-6xl items-center px-6 py-12">
        <div class="grid w-full gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <section>
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-4 py-2 text-sm font-bold text-warn">
                    <span>404</span>
                    <span>Page Not Found</span>
                </div>
                <h1 class="mt-6 max-w-2xl text-4xl font-extrabold tracking-tight sm:text-6xl">
                    The page you requested does not exist.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                    The URL may be incorrect, the page may have moved, or the resource may no longer be available.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ url('/') }}"
                       class="rounded-2xl bg-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Go To Home
                    </a>
                    <button onclick="window.history.back()"
                            class="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Go Back
                    </button>
                </div>
            </section>

            <aside class="rounded-[2rem] border border-sky-100 bg-white/80 p-8 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-mist text-3xl font-extrabold text-accent">
                    ?
                </div>
                <h2 class="mt-6 text-2xl font-bold">What you can check</h2>
                <ul class="mt-5 space-y-4 text-sm leading-6 text-slate-600">
                    <li>Check the URL for typing mistakes.</li>
                    <li>Return to the previous page and try a different link.</li>
                    <li>If this is a tenant URL, verify the organization slug is correct.</li>
                    <li>If you expected API data, use the API endpoint directly.</li>
                </ul>
            </aside>
        </div>
    </main>
</body>
</html>
