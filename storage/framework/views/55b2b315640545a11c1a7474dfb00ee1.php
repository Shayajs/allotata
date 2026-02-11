<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Connexion - Allo Tata</title>
        <?php echo $__env->make('partials.favicon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 sm:space-y-8">
            <div>
                <a href="<?php echo e(route('home')); ?>" class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <?php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    ?>
                    <?php if($logoUrl): ?>
                        <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>" class="h-12 w-auto sm:h-10">
                    <?php endif; ?>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        <?php echo e($siteName); ?>

                    </h1>
                </a>
                <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Connectez-vous à votre compte
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                    Ou
                    <a href="<?php echo e(route('signup')); ?>" class="font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                        créez un nouveau compte
                    </a>
                </p>
            </div>
            <form class="mt-6 sm:mt-8 space-y-5 sm:space-y-6 mobile-form" action="<?php echo e(route('login')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Adresse email
                        </label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            required 
                            value="<?php echo e(old('email')); ?>"
                            class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                            placeholder="votre@email.com"
                        >
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Mot de passe
                        </label>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-slate-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                            Se souvenir de moi
                        </label>
                    </div>
                    <div class="text-sm">
                        <a href="<?php echo e(route('password.request')); ?>" class="font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                            Mot de passe oublié ?
                        </a>
                    </div>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all touch-target"
                    >
                        Se connecter
                    </button>
                </div>
            </form>
        </div>
    </body>
</html>

<?php /**PATH /var/www/html/resources/views/auth/signin.blade.php ENDPATH**/ ?>