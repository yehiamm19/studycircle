<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · StudyCircle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans bg-slate-950 text-white flex items-center justify-center p-6" style="font-family:'Plus Jakarta Sans',sans-serif">
    <section class="text-center">
        <p class="text-8xl font-bold bg-gradient-to-r from-indigo-400 to-violet-400 bg-clip-text text-transparent">404</p>
        <h1 class="text-xl font-semibold mt-4 mb-2">Page not found</h1>
        <p class="text-slate-400 mb-8">The page you're looking for doesn't exist or was moved.</p>
        <a href="<?= url('/dashboard') ?>" class="inline-flex px-6 py-3 rounded-xl bg-indigo-600 font-semibold hover:bg-indigo-500 transition-colors">Back to Dashboard</a>
    </section>
</body>
</html>
