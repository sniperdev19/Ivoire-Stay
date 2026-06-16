<?php
/**
 * Page 404 autonome (requise directement par Router::dispatch, sans layout).
 * $base_url n'étant pas injecté ici, on le dérive de APP_URL.
 * @var string $base_url
 */
$base_url = rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#FAF7F2">
  <title>Page introuvable – Ivoire Stay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/vitrine.css">
</head>
<body class="min-h-screen flex items-center justify-center px-6 py-16 bg-[var(--color-cream)]">

  <!-- Halo décoratif doré -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="absolute -top-24 -left-24 w-[420px] h-[420px] rounded-full"
         style="background:radial-gradient(circle, rgba(201,168,76,0.18), transparent 70%);"></div>
    <div class="absolute -bottom-32 -right-20 w-[480px] h-[480px] rounded-full"
         style="background:radial-gradient(circle, rgba(27,67,50,0.10), transparent 70%);"></div>
  </div>

  <main class="relative glass-card-strong w-full max-w-xl text-center px-8 py-14 md:px-14">
    <a href="<?= $base_url ?>/" class="inline-block mb-8">
      <img src="<?= $base_url ?>/assets/logo.png" alt="Ivoire Stay" class="h-10 mx-auto w-auto object-contain">
    </a>

    <div class="font-display font-bold leading-none text-[var(--color-gold)]"
         style="font-size:clamp(88px,18vw,150px);">404</div>

    <h1 class="font-display text-[28px] md:text-[34px] text-[var(--color-forest)] mt-2">
      Cette page s'est égarée
    </h1>
    <p class="mt-4 text-[15px] md:text-[16px] text-[#4A5568] leading-7 max-w-md mx-auto">
      La page que vous cherchez n'existe pas ou a été déplacée.
      Revenez à l'accueil pour poursuivre votre séjour avec Ivoire Stay.
    </p>

    <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="<?= $base_url ?>/" class="btn-gold inline-flex items-center justify-center gap-2 px-8 py-4">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Retour à l'accueil
      </a>
      <a href="<?= $base_url ?>/search" class="btn-outline-gold inline-flex items-center justify-center gap-2 px-8 py-4">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        Explorer les destinations
      </a>
    </div>

    <div class="mt-8 text-[13px] text-[#718096]">
      Besoin d'aide ?
      <a href="<?= $base_url ?>/contact" class="text-[var(--color-gold)] font-medium hover:underline">Contactez-nous</a>
    </div>
  </main>

</body>
</html>
