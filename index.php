<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MOH Lunugamvehera Healthcare System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen flex items-center justify-center relative overflow-hidden">

    ```
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSoa3xoRYETYWzqncwF2lpPZkKDgOOc9EUGqDlsMC6bruTEmIh5GGW9We8&s=10"
            class="w-full h-full object-cover" />
    </div>

    <!-- Blur + Gradient Overlay -->
    <div class="absolute inset-0 backdrop-blur-sm bg-black/20"></div>

    <!-- Main Container -->
    <div class="relative z-10 text-center w-full max-w-5xl px-6">

        <!-- Title -->
        <h1 class="text-2xl md:text-5xl font-bold text-white mb-3">
            MOH Lunugamvehera Healthcare System
        </h1>

        <p class="text-white/80 mb-10">
            Simple. Smart. Efficient Public Healthcare Management
        </p>

        <!-- Cards -->
        <div class="grid md:grid-cols-3 gap-6">

            <!-- Admin Card -->
            <a href="login.php?role=admin"
                class="group backdrop-blur-xl bg-white/20 border border-white/30 rounded-2xl p-6 shadow-xl hover:scale-105 transition">

                <div class="flex justify-center mb-4">
                    <i data-lucide="shield" class="w-10 h-10 text-white group-hover:text-blue-200"></i>
                </div>

                <h2 class="text-xl font-semibold text-white">Admin Panel</h2>

                <p class="text-white/70 text-sm mt-2">
                    Manage users, healthcare staff, and system settings
                </p>

            </a>


            <!-- MOH Officer Card -->
            <a href="login.php?role=moh_officer"
                class="group backdrop-blur-xl bg-white/20 border border-white/30 rounded-2xl p-6 shadow-xl hover:scale-105 transition">

                <div class="flex justify-center mb-4">
                    <i data-lucide="hospital" class="w-10 h-10 text-white group-hover:text-green-200"></i>
                </div>

                <h2 class="text-xl font-semibold text-white">MOH Officer Panel</h2>

                <p class="text-white/70 text-sm mt-2">
                    Monitor patients, vaccinations, maternal health, and diseases
                </p>

            </a>


            <!-- Healthcare Staff Card -->
            <a href="login.php?role=staff"
                class="group backdrop-blur-xl bg-white/20 border border-white/30 rounded-2xl p-6 shadow-xl hover:scale-105 transition">

                <div class="flex justify-center mb-4">
                    <i data-lucide="stethoscope" class="w-10 h-10 text-white group-hover:text-pink-200"></i>
                </div>

                <h2 class="text-xl font-semibold text-white">Healthcare Staff Panel</h2>

                <p class="text-white/70 text-sm mt-2">
                    Manage patients, vaccinations, clinic visits, and health records
                </p>

            </a>

        </div>

        <!-- Footer -->
        <p class="text-white/60 text-xs mt-10">
            © 2026 MOH Lunugamvehera Healthcare System
        </p>

    </div>

    <script>
        lucide.createIcons();
    </script>
    ```

</body>

</html>