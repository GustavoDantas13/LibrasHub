<?php
session_start();

if (!empty($_SESSION["usuario_id"])) {
    header("Location: templates/home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta
        name="description"
        content="LibrasHub: tecnologia assistiva para tradução de LIBRAS, comunicação acessível e fortalecimento da comunidade surda."
    >
    <meta name="theme-color" content="#07111f">

    <title>LibrasHub — Tecnologia Assistiva, LIBRAS e Inclusão</title>

    <link rel="icon" type="image/png" href="static/images/librashub-logo.png">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >

    <!-- Tailwind via CDN: mantém o projeto compatível com PHP/XAMPP sem etapa de build. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Segoe UI', 'Arial', 'sans-serif']
                    },
                    boxShadow: {
                        soft: '0 20px 60px rgba(2, 12, 27, .10)',
                        glow: '0 18px 60px rgba(37, 99, 235, .28)'
                    }
                }
            }
        };
    </script>

    <style>
        /* =========================================================
           DESIGN TOKENS / ACESSIBILIDADE
           ========================================================= */

        :root {
            --font-scale: 1;

            --bg: #f7fbff;
            --surface: #ffffff;
            --surface-2: #eef7ff;
            --surface-dark: #07111f;

            --text: #0b1728;
            --muted: #526175;
            --border: #d9e6f2;

            --primary: #2563eb;
            --primary-2: #4f46e5;
            --cyan: #0f9f98;
            --cyan-soft: #d8f8f4;
            --yellow: #fdc500;

            --danger: #dc2626;
            --success: #0f8a5f;

            --nav-h: 74px;
            --radius: 24px;
        }

        html[data-theme="dark"] {
            --bg: #050b14;
            --surface: #0b1524;
            --surface-2: #101e31;
            --surface-dark: #020711;

            --text: #f3f8ff;
            --muted: #aebed1;
            --border: #24364c;

            --primary: #60a5fa;
            --primary-2: #818cf8;
            --cyan: #2dd4bf;
            --cyan-soft: #0b3533;
        }

        html.high-contrast {
            --bg: #000;
            --surface: #000;
            --surface-2: #000;
            --surface-dark: #000;

            --text: #fff;
            --muted: #fff;
            --border: #ffd900;

            --primary: #ffd900;
            --primary-2: #ffd900;
            --cyan: #00ffff;
            --cyan-soft: #000;
            --yellow: #ffd900;
        }

        html.high-contrast * {
            border-color: var(--border) !important;
            text-shadow: none !important;
            box-shadow: none !important;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: calc(var(--nav-h) + 16px);
            font-size: calc(16px * var(--font-scale));
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 85% 10%, rgba(37, 99, 235, .09), transparent 32rem),
                radial-gradient(circle at 15% 32%, rgba(15, 159, 152, .08), transparent 28rem),
                var(--bg);
            color: var(--text);
            transition: background .25s ease, color .25s ease;
        }

        a,
        button,
        input,
        textarea {
            -webkit-tap-highlight-color: transparent;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        img {
            max-width: 100%;
        }

        :focus-visible {
            outline: 3px solid var(--yellow);
            outline-offset: 3px;
            border-radius: 8px;
        }

        ::selection {
            background: var(--primary);
            color: #fff;
        }

        .skip-link {
            position: fixed;
            z-index: 9999;
            top: 10px;
            left: 10px;
            transform: translateY(-150%);
            padding: 10px 14px;
            border-radius: 10px;
            background: var(--yellow);
            color: #111827;
            font-weight: 800;
            transition: transform .2s ease;
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        .container-page {
            width: min(1180px, calc(100% - 40px));
            margin-inline: auto;
        }

        .section-pad {
            padding-block: clamp(78px, 9vw, 128px);
        }

        .section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: var(--primary);
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .section-kicker::before {
            content: "";
            width: 28px;
            height: 3px;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--primary), var(--cyan));
        }

        .section-title {
            max-width: 820px;
            margin: 0;
            font-size: clamp(2rem, 4.7vw, 4rem);
            line-height: 1.02;
            letter-spacing: -.045em;
            font-weight: 900;
            color: var(--text);
        }

        .section-copy {
            max-width: 720px;
            margin-top: 20px;
            color: var(--muted);
            font-size: clamp(1rem, 1.4vw, 1.12rem);
            line-height: 1.75;
        }

        .gradient-text {
            background: linear-gradient(90deg, var(--primary), var(--primary-2), var(--cyan));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card {
            background: color-mix(in srgb, var(--surface) 94%, transparent);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 18px 55px rgba(2, 12, 27, .06);
        }

        .glass {
            background: color-mix(in srgb, var(--surface) 78%, transparent);
            border: 1px solid color-mix(in srgb, var(--border) 78%, transparent);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .btn-brand,
        .btn-secondary,
        .btn-light {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 20px;
            border-radius: 13px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                background .18s ease,
                border-color .18s ease;
        }

        .btn-brand {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: #fff;
            box-shadow: 0 14px 34px rgba(37, 99, 235, .25);
        }

        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 42px rgba(37, 99, 235, .32);
        }

        .btn-secondary {
            color: var(--text);
            background: var(--surface);
            border-color: var(--border);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .btn-light {
            color: #07111f;
            background: #fff;
        }

        .btn-light:hover {
            transform: translateY(-2px);
        }

        /* =========================================================
           NAVBAR
           ========================================================= */

        .site-nav {
            position: fixed;
            inset: 0 0 auto;
            z-index: 1000;
            height: var(--nav-h);
            border-bottom: 1px solid rgba(255,255,255,.08);
            background: rgba(5, 12, 24, .88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .nav-inner {
            width: min(1220px, calc(100% - 32px));
            height: 100%;
            margin-inline: auto;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-size: 1.08rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            border-radius: 10px;
        }

        .brand strong span {
            color: var(--yellow);
        }

        .nav-links {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-links a {
            position: relative;
            padding: 10px 12px;
            border-radius: 10px;
            color: #cbd7e7;
            text-decoration: none;
            font-size: .86rem;
            font-weight: 700;
            transition: background .18s ease, color .18s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-actions a {
            min-height: 42px;
            padding: 9px 15px;
            border-radius: 11px;
            font-size: .84rem;
            font-weight: 800;
            text-decoration: none;
        }

        .nav-login {
            color: #fff;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
        }

        .nav-signup {
            color: #07111f;
            background: var(--yellow);
        }

        .hamburger {
            display: none;
            margin-left: auto;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 12px;
            background: rgba(255,255,255,.06);
            color: var(--yellow);
            cursor: pointer;
            font-size: 1.25rem;
        }

        .mobile-nav {
            display: none;
            position: fixed;
            z-index: 999;
            top: var(--nav-h);
            left: 0;
            right: 0;
            max-height: calc(100dvh - var(--nav-h));
            overflow-y: auto;
            padding: 16px;
            background: #07111f;
            border-top: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 20px 50px rgba(0,0,0,.34);
        }

        .mobile-nav.open {
            display: block;
        }

        .mobile-nav a {
            display: flex;
            min-height: 48px;
            align-items: center;
            padding: 10px 12px;
            color: #e5edf7;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.08);
            font-weight: 700;
        }

        .mobile-ctas {
            display: grid;
            gap: 10px;
            padding-top: 14px;
        }

        /* =========================================================
           TELA 1 — HERO / PROBLEMA
           ========================================================= */

        #problema {
            min-height: 100svh;
            display: flex;
            align-items: center;
            padding-top: calc(var(--nav-h) + 46px);
            padding-bottom: 56px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.04fr) minmax(400px, .96fr);
            align-items: center;
            gap: clamp(42px, 6vw, 84px);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            margin-bottom: 20px;
            border: 1px solid color-mix(in srgb, var(--primary) 25%, var(--border));
            border-radius: 999px;
            background: color-mix(in srgb, var(--primary) 9%, var(--surface));
            color: var(--primary);
            font-size: .76rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .hero-title {
            margin: 0;
            max-width: 790px;
            font-size: clamp(2.6rem, 6vw, 5.7rem);
            line-height: .96;
            letter-spacing: -.06em;
            font-weight: 950;
        }

        .hero-lead {
            max-width: 690px;
            margin: 26px 0 0;
            color: var(--muted);
            font-size: clamp(1.02rem, 1.55vw, 1.2rem);
            line-height: 1.75;
        }

        .hero-lead strong {
            color: var(--text);
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .hero-proof {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 12px;
            margin-top: 38px;
        }

        .proof-item {
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: color-mix(in srgb, var(--surface) 80%, transparent);
        }

        .proof-item strong {
            display: block;
            margin-bottom: 4px;
            font-size: 1.16rem;
        }

        .proof-item span {
            color: var(--muted);
            font-size: .76rem;
            line-height: 1.4;
        }

        .hero-visual {
            position: relative;
            min-height: 590px;
        }

        .hero-orbit {
            position: absolute;
            inset: 5% 0 auto auto;
            width: 430px;
            height: 430px;
            border: 1px solid rgba(37,99,235,.17);
            border-radius: 50%;
            animation: rotateSlow 28s linear infinite;
        }

        .hero-orbit::before,
        .hero-orbit::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 0 8px rgba(15,159,152,.12);
        }

        .hero-orbit::before {
            top: 12%;
            left: 17%;
        }

        .hero-orbit::after {
            right: 7%;
            bottom: 24%;
            background: var(--primary);
            box-shadow: 0 0 0 8px rgba(37,99,235,.12);
        }

        @keyframes rotateSlow {
            to { transform: rotate(360deg); }
        }

        .device {
            position: absolute;
            z-index: 2;
            top: 50%;
            left: 50%;
            width: min(100%, 420px);
            transform: translate(-50%, -50%) rotate(-2deg);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 32px;
            background: #07111f;
            padding: 14px;
            box-shadow: 0 32px 80px rgba(1, 8, 20, .28);
        }

        .device-screen {
            overflow: hidden;
            border-radius: 24px;
            background:
                radial-gradient(circle at 80% 20%, rgba(45,212,191,.18), transparent 35%),
                radial-gradient(circle at 20% 75%, rgba(96,165,250,.22), transparent 40%),
                #0b1728;
            min-height: 510px;
            padding: 18px;
            color: #fff;
        }

        .device-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .72rem;
            color: #a9bdd4;
        }

        .camera-frame {
            position: relative;
            height: 280px;
            margin-top: 22px;
            border-radius: 22px;
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(37,99,235,.28), rgba(15,159,152,.15)),
                #101e31;
            border: 1px solid rgba(255,255,255,.10);
        }

        .camera-grid {
            position: absolute;
            inset: 0;
            opacity: .24;
            background-image:
                linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .hand-symbol {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            font-size: 4.7rem;
        }

        .scan-line {
            position: absolute;
            left: 10%;
            right: 10%;
            top: 25%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #2dd4bf, transparent);
            box-shadow: 0 0 16px #2dd4bf;
            animation: scan 3s ease-in-out infinite;
        }

        @keyframes scan {
            0%, 100% { top: 24%; opacity: .3; }
            50% { top: 72%; opacity: 1; }
        }

        .translation-box {
            margin-top: 16px;
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.09);
        }

        .translation-label {
            color: #8ea9c5;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            font-weight: 800;
        }

        .translation-text {
            margin-top: 6px;
            font-size: 1.1rem;
            font-weight: 850;
        }

        .floating-note {
            position: absolute;
            z-index: 3;
            padding: 14px 16px;
            min-width: 180px;
            border-radius: 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 18px 40px rgba(2,12,27,.12);
        }

        .floating-note strong {
            display: block;
            font-size: .78rem;
        }

        .floating-note span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: .7rem;
        }

        .note-a {
            left: -4%;
            top: 13%;
        }

        .note-b {
            right: -6%;
            bottom: 14%;
        }

        /* =========================================================
           TELA 2 — SOLUÇÃO
           ========================================================= */

        #solucao {
            background: color-mix(in srgb, var(--surface-2) 66%, transparent);
            border-block: 1px solid var(--border);
        }

        .solution-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 18px;
            margin-top: 48px;
        }

        .solution-card {
            position: relative;
            overflow: hidden;
            padding: 28px;
            transition: transform .24s ease, border-color .24s ease;
        }

        .solution-card:hover {
            transform: translateY(-6px);
            border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
        }

        .solution-card::after {
            content: "";
            position: absolute;
            right: -50px;
            top: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--primary) 9%, transparent);
        }

        .icon-box {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin-bottom: 22px;
            border-radius: 16px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 16%, var(--surface)), color-mix(in srgb, var(--cyan) 16%, var(--surface)));
            color: var(--primary);
            font-size: 1.25rem;
        }

        .solution-card h3 {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 900;
        }

        .solution-card p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.68;
            font-size: .9rem;
        }

        /* =========================================================
           TELA 3 — COMO FUNCIONA
           ========================================================= */

        .timeline {
            position: relative;
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 24px;
            margin-top: 54px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 30px;
            left: 16%;
            right: 16%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--cyan));
            opacity: .3;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
        }

        .step-node {
            width: 62px;
            height: 62px;
            display: grid;
            place-items: center;
            margin-bottom: 20px;
            border-radius: 20px;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--cyan));
            box-shadow: 0 15px 35px rgba(37,99,235,.18);
            font-size: 1.15rem;
            font-weight: 900;
        }

        .timeline-step h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 900;
        }

        .timeline-step p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.68;
            font-size: .92rem;
        }

        .step-tag {
            display: inline-block;
            margin-top: 14px;
            padding: 6px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--cyan) 13%, var(--surface));
            color: var(--cyan);
            font-size: .68rem;
            font-weight: 900;
        }

        /* =========================================================
           TELA 4 — TECNOLOGIA
           ========================================================= */

        #tecnologia {
            color: #eef6ff;
            background:
                radial-gradient(circle at 12% 20%, rgba(37,99,235,.28), transparent 28rem),
                radial-gradient(circle at 85% 75%, rgba(45,212,191,.18), transparent 32rem),
                #050c18;
        }

        #tecnologia .section-title {
            color: #fff;
        }

        #tecnologia .section-copy {
            color: #a9bed4;
        }

        #tecnologia .section-kicker {
            color: #7dd3fc;
        }

        .tech-layout {
            display: grid;
            grid-template-columns: minmax(0,.9fr) minmax(0,1.1fr);
            align-items: center;
            gap: clamp(40px, 7vw, 90px);
        }

        .tech-list {
            display: grid;
            gap: 14px;
            margin-top: 34px;
        }

        .tech-item {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 14px;
            padding: 16px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 16px;
            background: rgba(255,255,255,.045);
        }

        .tech-item i {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: rgba(96,165,250,.12);
            color: #7dd3fc;
        }

        .tech-item strong {
            display: block;
            color: #fff;
            font-size: .9rem;
        }

        .tech-item span {
            display: block;
            margin-top: 4px;
            color: #9eb2c8;
            line-height: 1.55;
            font-size: .8rem;
        }

        .network-card {
            position: relative;
            min-height: 560px;
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.10);
            background:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px),
                #081321;
            background-size: 38px 38px;
        }

        .network-core {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%,-50%);
            width: 150px;
            height: 150px;
            display: grid;
            place-items: center;
            text-align: center;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 25%, #60a5fa, #2563eb 45%, #4338ca);
            box-shadow:
                0 0 0 20px rgba(37,99,235,.08),
                0 0 70px rgba(37,99,235,.42);
            color: #fff;
        }

        .network-core strong {
            display: block;
            font-size: 1.2rem;
        }

        .network-core span {
            display: block;
            margin-top: 2px;
            font-size: .7rem;
            opacity: .8;
        }

        .node {
            position: absolute;
            width: 116px;
            min-height: 72px;
            display: grid;
            place-items: center;
            text-align: center;
            padding: 10px;
            border-radius: 16px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            color: #dcecff;
            font-size: .72rem;
            font-weight: 800;
            box-shadow: 0 12px 30px rgba(0,0,0,.18);
        }

        .node i {
            display: block;
            margin-bottom: 5px;
            color: #5eead4;
            font-size: 1rem;
        }

        .n1 { top: 11%; left: 10%; }
        .n2 { top: 11%; right: 10%; }
        .n3 { bottom: 12%; left: 9%; }
        .n4 { bottom: 12%; right: 9%; }

        .network-line {
            position: absolute;
            inset: 50% auto auto 50%;
            width: 44%;
            height: 1px;
            transform-origin: 0 50%;
            background: linear-gradient(90deg, rgba(96,165,250,.8), transparent);
            opacity: .45;
        }

        .l1 { transform: rotate(215deg); }
        .l2 { transform: rotate(325deg); }
        .l3 { transform: rotate(145deg); }
        .l4 { transform: rotate(35deg); }

        /* =========================================================
           TELA 5 — RESULTADOS / INDICADORES
           ========================================================= */

        .metrics-note {
            display: inline-flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 22px;
            padding: 12px 14px;
            border-radius: 14px;
            color: var(--muted);
            background: color-mix(in srgb, var(--yellow) 9%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--yellow) 35%, var(--border));
            font-size: .78rem;
            line-height: 1.5;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 16px;
            margin-top: 46px;
        }

        .metric-card {
            min-height: 230px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-number {
            font-size: clamp(2.4rem, 5vw, 4.4rem);
            line-height: .9;
            letter-spacing: -.055em;
            font-weight: 950;
            color: var(--text);
        }

        .metric-number span {
            color: var(--primary);
        }

        .metric-card h3 {
            margin: 18px 0 0;
            font-size: .94rem;
            font-weight: 900;
        }

        .metric-card p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.55;
        }

        .mini-bars {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            height: 42px;
            margin-top: 16px;
        }

        .mini-bars span {
            flex: 1;
            min-width: 5px;
            border-radius: 5px 5px 2px 2px;
            background: linear-gradient(180deg, var(--cyan), var(--primary));
            opacity: .75;
        }

        /* =========================================================
           TELA 6 — IMPACTO HUMANO
           ========================================================= */

        #impacto {
            background: color-mix(in srgb, var(--surface-2) 70%, transparent);
            border-block: 1px solid var(--border);
        }

        .impact-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr .9fr;
            grid-template-rows: auto auto;
            gap: 16px;
            margin-top: 48px;
        }

        .impact-card {
            position: relative;
            overflow: hidden;
            padding: 28px;
            min-height: 250px;
        }

        .impact-card.main {
            grid-row: span 2;
            min-height: 516px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(circle at 100% 0, rgba(37,99,235,.18), transparent 20rem),
                var(--surface);
        }

        .quote-mark {
            font-size: 4rem;
            line-height: 1;
            color: color-mix(in srgb, var(--primary) 45%, transparent);
        }

        .impact-card blockquote {
            margin: 20px 0;
            font-size: clamp(1.05rem, 2vw, 1.45rem);
            line-height: 1.6;
            font-weight: 750;
        }

        .impact-card p {
            color: var(--muted);
            line-height: 1.65;
            font-size: .86rem;
        }

        .persona {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--cyan));
            color: #fff;
            font-weight: 900;
        }

        .persona strong {
            display: block;
            font-size: .86rem;
        }

        .persona span {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: .72rem;
        }

        .scenario-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--cyan) 13%, var(--surface));
            color: var(--cyan);
            font-size: .66rem;
            font-weight: 900;
        }

        /* =========================================================
           TELA 7 — EQUIPE
           ========================================================= */

        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 18px;
            margin-top: 48px;
        }

        .team-card {
            position: relative;
            overflow: hidden;
            padding: 24px;
            text-align: center;
            transition: transform .22s ease, border-color .22s ease;
        }

        .team-card:hover {
            transform: translateY(-6px);
            border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
        }

        .team-photo {
            width: 104px;
            height: 104px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            border-radius: 30px;
            background:
                radial-gradient(circle at 30% 20%, rgba(255,255,255,.38), transparent 32%),
                linear-gradient(135deg, var(--primary), var(--cyan));
            color: #fff;
            font-size: 1.5rem;
            font-weight: 950;
            box-shadow: 0 18px 36px rgba(37,99,235,.18);
        }

        .team-card h3 {
            margin: 0;
            font-size: .98rem;
            font-weight: 900;
        }

        .team-role {
            margin-top: 5px;
            color: var(--primary);
            font-size: .72rem;
            font-weight: 900;
        }

        .team-card p {
            margin: 12px 0 0;
            color: var(--muted);
            line-height: 1.55;
            font-size: .78rem;
        }

        .team-socials {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            opacity: .55;
            transition: opacity .2s ease;
        }

        .team-card:hover .team-socials {
            opacity: 1;
        }

        .team-socials a {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 11px;
            color: var(--text);
            text-decoration: none;
            background: var(--surface);
        }

        /* =========================================================
           TELA 8 — APOIE O PROJETO
           ========================================================= */

        #apoie {
            position: relative;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 15% 15%, rgba(45,212,191,.22), transparent 24rem),
                radial-gradient(circle at 85% 75%, rgba(129,140,248,.34), transparent 28rem),
                linear-gradient(135deg, #07111f 0%, #172554 48%, #312e81 100%);
        }

        #apoie::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .18;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
            background-size: 46px 46px;
        }

        .support-wrap {
            position: relative;
            z-index: 2;
        }

        #apoie .section-kicker {
            color: #8be7df;
        }

        #apoie .section-title {
            color: #fff;
            max-width: 930px;
        }

        #apoie .section-copy {
            color: #c6d5e8;
            max-width: 820px;
        }

        .support-grid {
            display: grid;
            grid-template-columns: 1.15fr .85fr .85fr;
            gap: 18px;
            margin-top: 48px;
        }

        .support-card {
            padding: 26px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(16px);
        }

        .support-card.featured {
            background: rgba(255,255,255,.12);
            box-shadow: 0 24px 70px rgba(0,0,0,.22);
        }

        .support-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: rgba(255,255,255,.10);
            color: var(--yellow);
            font-size: 1.25rem;
        }

        .support-card h3 {
            margin: 18px 0 0;
            font-size: 1.08rem;
            font-weight: 900;
        }

        .support-card p {
            margin: 10px 0 0;
            color: #c9d6e6;
            line-height: 1.62;
            font-size: .82rem;
        }

        .support-list {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .support-list span {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            color: #d9e7f5;
            font-size: .76rem;
        }

        .support-list i {
            margin-top: 2px;
            color: #5eead4;
        }

        .sponsor-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .sponsor-form .full {
            grid-column: 1 / -1;
        }

        .sponsor-form input,
        .sponsor-form textarea {
            width: 100%;
            min-height: 46px;
            padding: 11px 12px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 12px;
            background: rgba(255,255,255,.08);
            color: #fff;
        }

        .sponsor-form textarea {
            min-height: 92px;
            resize: vertical;
        }

        .sponsor-form input::placeholder,
        .sponsor-form textarea::placeholder {
            color: #9eb3cb;
        }

        .sponsor-form label {
            display: block;
            margin-bottom: 5px;
            color: #dce8f5;
            font-size: .7rem;
            font-weight: 800;
        }

        .support-cta {
            width: 100%;
            margin-top: 20px;
        }

        .support-small {
            margin-top: 10px;
            color: #9fb4cc;
            font-size: .68rem;
            line-height: 1.45;
        }

        .support-manifesto {
            margin-top: 48px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .support-manifesto strong {
            max-width: 780px;
            font-size: clamp(1.1rem, 2vw, 1.55rem);
            line-height: 1.5;
        }

        /* =========================================================
           RODAPÉ
           ========================================================= */

        footer {
            padding: 50px 0 32px;
            color: #d5e1ef;
            background: #020711;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 36px;
        }

        .footer-brand p {
            max-width: 340px;
            margin-top: 14px;
            color: #8ca3bc;
            line-height: 1.65;
            font-size: .8rem;
        }

        .footer-social {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: var(--yellow);
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.09);
        }

        .footer-col h4 {
            margin: 0 0 13px;
            color: #fff;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .09em;
        }

        .footer-col a {
            display: block;
            margin: 9px 0;
            color: #97abc2;
            font-size: .8rem;
            text-decoration: none;
        }

        .footer-col a:hover {
            color: #fff;
        }

        .footer-bottom {
            margin-top: 36px;
            padding-top: 22px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid rgba(255,255,255,.08);
            color: #7489a0;
            font-size: .72rem;
        }

        /* =========================================================
           ACESSIBILIDADE + ESPAÇO PARA WIDGET LIBRAS
           ========================================================= */

        .a11y-wrap {
            position: fixed;
            z-index: 1400;
            right: 18px;
            bottom: 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .a11y-panel {
            display: none;
            width: min(290px, calc(100vw - 28px));
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            color: var(--text);
            box-shadow: 0 20px 55px rgba(2,12,27,.16);
        }

        .a11y-panel.open {
            display: block;
        }

        .a11y-panel h3 {
            margin: 0 0 12px;
            font-size: .86rem;
            font-weight: 900;
        }

        .a11y-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid var(--border);
        }

        .a11y-row:first-of-type {
            border-top: 0;
        }

        .a11y-row span {
            font-size: .76rem;
            font-weight: 750;
        }

        .a11y-mini {
            display: flex;
            gap: 6px;
        }

        .a11y-mini button,
        .a11y-toggle {
            min-width: 40px;
            min-height: 38px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface-2);
            color: var(--text);
            cursor: pointer;
            font-weight: 900;
        }

        .a11y-mini button.active,
        .a11y-toggle.active {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .a11y-fab {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: #fff;
            cursor: pointer;
            box-shadow: 0 15px 36px rgba(37,99,235,.32);
            font-size: 1.2rem;
        }

        .libras-widget-slot {
            position: fixed;
            z-index: 1300;
            left: 18px;
            bottom: 18px;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            border: 1px dashed color-mix(in srgb, var(--cyan) 55%, var(--border));
            border-radius: 14px;
            background: color-mix(in srgb, var(--surface) 92%, transparent);
            color: var(--text);
            box-shadow: 0 14px 36px rgba(2,12,27,.10);
            font-size: .72rem;
            font-weight: 800;
        }

        .libras-widget-slot i {
            color: var(--cyan);
            font-size: 1rem;
        }

        /* =========================================================
           ANIMAÇÕES DE SCROLL
           ========================================================= */

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity .65s ease, transform .65s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: none;
        }

        @supports (animation-timeline: view()) {
            .scroll-driven {
                animation: enter linear both;
                animation-timeline: view();
                animation-range: entry 10% cover 35%;
            }

            @keyframes enter {
                from {
                    opacity: 0;
                    transform: translateY(40px) scale(.985);
                }
                to {
                    opacity: 1;
                    transform: none;
                }
            }
        }

        /* =========================================================
           RESPONSIVIDADE
           ========================================================= */

        @media (max-width: 1080px) {
            .nav-links {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .nav-actions {
                margin-left: auto;
            }

            .hero-grid {
                grid-template-columns: minmax(0,1fr) minmax(350px,.85fr);
            }

            .solution-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .metrics-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .team-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .support-grid {
                grid-template-columns: 1fr 1fr;
            }

            .support-card.featured {
                grid-column: 1 / -1;
            }

            .footer-grid {
                grid-template-columns: 1.2fr 1fr 1fr;
            }

            .footer-col:last-child {
                grid-column: 2 / -1;
            }
        }

        @media (max-width: 820px) {
            .container-page {
                width: min(100% - 28px, 1180px);
            }

            .nav-actions {
                display: none;
            }

            .hero-grid {
                grid-template-columns: 1fr;
            }

            #problema {
                min-height: auto;
                padding-top: calc(var(--nav-h) + 58px);
            }

            .hero-visual {
                min-height: 520px;
            }

            .hero-proof {
                grid-template-columns: 1fr;
            }

            .solution-grid {
                grid-template-columns: 1fr;
            }

            .timeline {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .timeline::before {
                top: 0;
                bottom: 0;
                left: 30px;
                right: auto;
                width: 2px;
                height: auto;
            }

            .timeline-step {
                padding-left: 86px;
                min-height: 110px;
            }

            .step-node {
                position: absolute;
                left: 0;
                top: 0;
            }

            .tech-layout {
                grid-template-columns: 1fr;
            }

            .network-card {
                min-height: 500px;
            }

            .impact-grid {
                grid-template-columns: 1fr 1fr;
            }

            .impact-card.main {
                grid-column: 1 / -1;
                grid-row: auto;
                min-height: 360px;
            }

            .support-grid {
                grid-template-columns: 1fr;
            }

            .support-card.featured {
                grid-column: auto;
            }

            .support-manifesto {
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-col:last-child {
                grid-column: auto;
            }
        }

        @media (max-width: 560px) {
            :root {
                --nav-h: 66px;
                --radius: 19px;
            }

            .container-page {
                width: min(100% - 22px, 1180px);
            }

            .section-pad {
                padding-block: 70px;
            }

            .nav-inner {
                width: calc(100% - 20px);
            }

            .brand img {
                width: 34px;
                height: 34px;
            }

            .brand {
                font-size: .98rem;
            }

            .hamburger {
                width: 44px;
                height: 44px;
            }

            .hero-title {
                font-size: clamp(2.35rem, 13vw, 3.5rem);
            }

            .hero-lead {
                font-size: .96rem;
            }

            .hero-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .hero-actions a {
                width: 100%;
            }

            .hero-visual {
                min-height: 440px;
            }

            .device {
                width: min(100%, 355px);
            }

            .device-screen {
                min-height: 405px;
            }

            .camera-frame {
                height: 210px;
            }

            .hero-orbit,
            .floating-note {
                display: none;
            }

            .metrics-grid,
            .team-grid,
            .impact-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .metric-card {
                min-height: 200px;
            }

            .impact-card.main {
                grid-column: auto;
            }

            .network-card {
                min-height: 430px;
            }

            .node {
                width: 92px;
                min-height: 62px;
                font-size: .62rem;
            }

            .network-core {
                width: 122px;
                height: 122px;
            }

            .sponsor-form {
                grid-template-columns: 1fr;
            }

            .sponsor-form .full {
                grid-column: auto;
            }

            .support-card {
                padding: 20px;
            }

            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }

            .libras-widget-slot {
                left: 10px;
                bottom: 10px;
                width: 46px;
                height: 46px;
                padding: 0;
                justify-content: center;
                border-radius: 50%;
            }

            .libras-widget-slot span {
                display: none;
            }

            .a11y-wrap {
                right: 10px;
                bottom: 10px;
            }

            .a11y-fab {
                width: 50px;
                height: 50px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }

            .reveal {
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>

<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<!-- =============================================================
     NAVEGAÇÃO
     ============================================================= -->
<nav class="site-nav" id="navbar" aria-label="Navegação principal">
    <div class="nav-inner">
        <a class="brand" href="index.php" aria-label="LibrasHub — início">
            <img src="static/images/librashub-logo.png" alt="">
            <strong>Libras<span>Hub</span></strong>
        </a>

        <div class="nav-links" id="navLinks">
            <a href="#problema">Problema</a>
            <a href="#solucao">Solução</a>
            <a href="#funciona">Como funciona</a>
            <a href="#tecnologia">Tecnologia</a>
            <a href="#impacto">Impacto</a>
            <a href="#equipe">Equipe</a>
            <a href="#apoie">Apoie</a>
        </div>

        <div class="nav-actions">
            <a class="nav-login" href="templates/login.php">Entrar</a>
            <a class="nav-signup" href="templates/cadastro.php">Criar conta</a>
        </div>

        <button
            class="hamburger"
            id="hamburger"
            type="button"
            aria-label="Abrir menu"
            aria-expanded="false"
            aria-controls="mobileNav"
        >
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
    </div>
</nav>

<div class="mobile-nav" id="mobileNav" aria-label="Menu mobile">
    <a href="#problema" data-mobile-link>Problema</a>
    <a href="#solucao" data-mobile-link>Solução</a>
    <a href="#funciona" data-mobile-link>Como funciona</a>
    <a href="#tecnologia" data-mobile-link>Tecnologia</a>
    <a href="#resultados" data-mobile-link>Resultados</a>
    <a href="#impacto" data-mobile-link>Impacto</a>
    <a href="#equipe" data-mobile-link>Equipe</a>
    <a href="#apoie" data-mobile-link>Apoie o projeto</a>

    <div class="mobile-ctas">
        <a class="btn-secondary" href="templates/login.php">Entrar</a>
        <a class="btn-brand" href="templates/cadastro.php">Criar conta</a>
    </div>
</div>

<main id="conteudo">

    <!-- =========================================================
         TELA 1 — HERO / O PROBLEMA
         ========================================================= -->
    <section id="problema">
        <div class="container-page hero-grid">
            <div class="reveal">
                <div class="hero-badge">
                    <i class="fa-solid fa-hands-asl-interpreting" aria-hidden="true"></i>
                    Tecnologia assistiva para comunicação sem barreiras
                </div>

                <h1 class="hero-title">
                    Comunicação não deveria ser um
                    <span class="gradient-text">privilégio.</span>
                </h1>

                <p class="hero-lead">
                    Mais de <strong>10 milhões de pessoas surdas e com deficiência auditiva no Brasil</strong>
                    convivem diariamente com barreiras de comunicação. Em uma sala de aula, entrevista,
                    atendimento ou conversa simples, a ausência de ferramentas acessíveis ainda transforma
                    autonomia em dependência.
                </p>

                <div class="hero-actions">
                    <a class="btn-brand" href="#solucao">
                        Conhecer a solução
                        <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                    </a>

                    <a class="btn-secondary" href="templates/leitor.php">
                        <i class="fa-solid fa-video" aria-hidden="true"></i>
                        Testar tradução
                    </a>
                </div>

                <div class="hero-proof" aria-label="Pilares da proposta">
                    <div class="proof-item">
                        <strong>LIBRAS + IA</strong>
                        <span>Visão computacional aplicada à tradução de sinais.</span>
                    </div>

                    <div class="proof-item">
                        <strong>Comunidade</strong>
                        <span>Um espaço que aproxima pessoas, conhecimento e apoio.</span>
                    </div>

                    <div class="proof-item">
                        <strong>Acessibilidade</strong>
                        <span>Experiência pensada para diferentes formas de interação.</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual reveal" aria-label="Demonstração visual do sistema LibrasHub">
                <div class="hero-orbit" aria-hidden="true"></div>

                <div class="floating-note note-a" aria-hidden="true">
                    <strong>Barreira detectada</strong>
                    <span>Comunicação sem intérprete disponível</span>
                </div>

                <div class="device" aria-hidden="true">
                    <div class="device-screen">
                        <div class="device-top">
                            <span>LIBRASHUB / TRADUÇÃO AO VIVO</span>
                            <span><i class="fa-solid fa-shield-halved"></i> conexão segura</span>
                        </div>

                        <div class="camera-frame">
                            <div class="camera-grid"></div>
                            <div class="hand-symbol">🤟</div>
                            <div class="scan-line"></div>
                        </div>

                        <div class="translation-box">
                            <div class="translation-label">Resultado em tempo real</div>
                            <div class="translation-text">“Olá. Como posso ajudar você?”</div>
                        </div>

                        <div class="translation-box">
                            <div class="translation-label">Próximo passo</div>
                            <div class="translation-text" style="font-size:.88rem;font-weight:700;color:#bdd3e8;">
                                Texto, voz e conexão com a comunidade.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-note note-b" aria-hidden="true">
                    <strong>Ponte criada pela tecnologia</strong>
                    <span>Mais independência para comunicar e participar</span>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 2 — A SOLUÇÃO
         ========================================================= -->
    <section id="solucao" class="section-pad">
        <div class="container-page">
            <div class="section-kicker reveal">A solução</div>

            <h2 class="section-title reveal">
                Um ecossistema para
                <span class="gradient-text">traduzir, compreender e conectar.</span>
            </h2>

            <p class="section-copy reveal">
                O LibrasHub une tradução assistida por inteligência artificial, recursos acessíveis
                e comunidade em uma única experiência. O objetivo não é apenas converter sinais em texto:
                é tornar a comunicação mais rápida, natural e disponível quando ela realmente importa.
            </p>

            <div class="solution-grid">
                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-camera"></i></div>
                    <h3>LIBRAS → Português</h3>
                    <p>
                        A câmera identifica movimentos e sinais para gerar uma interpretação em texto,
                        preparada para também ser reproduzida por voz.
                    </p>
                </article>

                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-language"></i></div>
                    <h3>Português → LIBRAS</h3>
                    <p>
                        A arquitetura já reserva o fluxo inverso para transformar texto ou fala em uma
                        representação acessível em LIBRAS, ampliando a comunicação bidirecional.
                    </p>
                </article>

                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                    <h3>Comunidade ativa</h3>
                    <p>
                        Usuários comuns e comunitários podem encontrar conteúdo, trocar experiências
                        e fortalecer uma rede criada para acolhimento, aprendizado e participação.
                    </p>
                </article>

                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-upload"></i></div>
                    <h3>Tradução por upload</h3>
                    <p>
                        Fotos e vídeos podem ser enviados para análise quando a tradução ao vivo não for
                        a melhor opção ou quando o conteúdo já estiver gravado.
                    </p>
                </article>

                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <h3>Histórico inteligente</h3>
                    <p>
                        Traduções realizadas por usuários autenticados podem ser consultadas novamente,
                        facilitando continuidade, estudo e organização.
                    </p>
                </article>

                <article class="card solution-card scroll-driven">
                    <div class="icon-box"><i class="fa-solid fa-universal-access"></i></div>
                    <h3>Acessibilidade por padrão</h3>
                    <p>
                        Contraste, tamanho de fonte, leitura de texto e espaço para widgets de LIBRAS
                        fazem parte da interface — não são um recurso adicionado no fim.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 3 — COMO FUNCIONA
         ========================================================= -->
    <section id="funciona" class="section-pad">
        <div class="container-page">
            <div class="section-kicker reveal">Como funciona</div>

            <h2 class="section-title reveal">
                Três passos.
                <span class="gradient-text">Nenhuma complicação.</span>
            </h2>

            <p class="section-copy reveal">
                O fluxo foi pensado para funcionar até para quem não entende nada de inteligência artificial.
                A tecnologia fica nos bastidores; na frente, o usuário vê uma experiência simples e direta.
            </p>

            <div class="timeline">
                <article class="timeline-step reveal">
                    <div class="step-node">1</div>
                    <h3>Captura</h3>
                    <p>
                        Use a câmera para sinais em LIBRAS, envie uma imagem/vídeo ou prepare a entrada
                        por áudio e texto para os fluxos de comunicação em português.
                    </p>
                    <span class="step-tag">Entrada multimodal</span>
                </article>

                <article class="timeline-step reveal">
                    <div class="step-node">2</div>
                    <h3>Processamento inteligente</h3>
                    <p>
                        O sistema analisa os dados, identifica padrões e executa o fluxo de tradução
                        com foco em velocidade, contexto e segurança.
                    </p>
                    <span class="step-tag">IA + visão computacional</span>
                </article>

                <article class="timeline-step reveal">
                    <div class="step-node">3</div>
                    <h3>Inclusão</h3>
                    <p>
                        O resultado é entregue em um formato acessível e pode continuar no histórico
                        ou na rede comunitária, transformando tradução em participação.
                    </p>
                    <span class="step-tag">Resultado + comunidade</span>
                </article>
            </div>

            <div class="hero-actions reveal" style="margin-top:44px;">
                <a class="btn-brand" href="templates/leitor.php">
                    <i class="fa-solid fa-video"></i>
                    Abrir tradução ao vivo
                </a>

                <a class="btn-secondary" href="templates/upload.php">
                    <i class="fa-solid fa-upload"></i>
                    Traduzir arquivo
                </a>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 4 — TECNOLOGIA
         ========================================================= -->
    <section id="tecnologia" class="section-pad">
        <div class="container-page tech-layout">
            <div>
                <div class="section-kicker reveal">Tecnologia</div>

                <h2 class="section-title reveal">
                    Engenharia para transformar
                    <span style="color:#67e8f9;">movimento em significado.</span>
                </h2>

                <p class="section-copy reveal">
                    A proposta técnica do LibrasHub combina processamento visual, inteligência artificial,
                    APIs e uma arquitetura preparada para evoluir junto com a comunidade.
                </p>

                <div class="tech-list">
                    <div class="tech-item reveal">
                        <i class="fa-solid fa-eye"></i>
                        <div>
                            <strong>Visão Computacional</strong>
                            <span>Leitura de sinais capturados pela câmera para identificar gestos e padrões visuais.</span>
                        </div>
                    </div>

                    <div class="tech-item reveal">
                        <i class="fa-solid fa-brain"></i>
                        <div>
                            <strong>Inteligência Artificial treinável</strong>
                            <span>Modelo preparado para evoluir com novos exemplos, correções e validações do conjunto de sinais.</span>
                        </div>
                    </div>

                    <div class="tech-item reveal">
                        <i class="fa-solid fa-diagram-project"></i>
                        <div>
                            <strong>APIs escaláveis</strong>
                            <span>Separação entre interface, processamento e dados para permitir integrações futuras sem reconstruir o produto.</span>
                        </div>
                    </div>

                    <div class="tech-item reveal">
                        <i class="fa-solid fa-shield-halved"></i>
                        <div>
                            <strong>Privacidade e segurança</strong>
                            <span>Sessões autenticadas, validação de dados e arquitetura orientada à proteção das informações do usuário.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="network-card reveal" aria-label="Representação abstrata da arquitetura de tecnologia">
                <div class="network-line l1"></div>
                <div class="network-line l2"></div>
                <div class="network-line l3"></div>
                <div class="network-line l4"></div>

                <div class="network-core">
                    <div>
                        <strong>IA LibrasHub</strong>
                        <span>motor de tradução</span>
                    </div>
                </div>

                <div class="node n1">
                    <div>
                        <i class="fa-solid fa-camera"></i>
                        Câmera / visão
                    </div>
                </div>

                <div class="node n2">
                    <div>
                        <i class="fa-solid fa-database"></i>
                        Base de sinais
                    </div>
                </div>

                <div class="node n3">
                    <div>
                        <i class="fa-solid fa-code"></i>
                        APIs / sistema
                    </div>
                </div>

                <div class="node n4">
                    <div>
                        <i class="fa-solid fa-users"></i>
                        Comunidade
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 5 — RESULTADOS
         ========================================================= -->
    <section id="resultados" class="section-pad">
        <div class="container-page">
            <div class="section-kicker reveal">Resultados</div>

            <h2 class="section-title reveal">
                Métricas que transformam tecnologia
                <span class="gradient-text">em confiança.</span>
            </h2>

            <p class="section-copy reveal">
                Esta área foi estruturada para apresentar indicadores de validação do protótipo sem
                transformar metas em resultados fictícios. Conecte aqui os dados reais dos seus testes finais.
            </p>

            <div class="metrics-note reveal">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>
                    Os valores abaixo que usam “meta” ou “planejado” são objetivos técnicos da interface.
                    Substitua por métricas medidas quando os testes do modelo estiverem consolidados.
                </span>
            </div>

            <div class="metrics-grid">
                <article class="card metric-card reveal">
                    <div>
                        <div class="metric-number">&lt;<span>1s</span></div>
                        <h3>Meta de resposta</h3>
                        <p>Objetivo de experiência para interações de tradução em tempo real.</p>
                    </div>
                    <div class="mini-bars" aria-hidden="true">
                        <span style="height:32%"></span>
                        <span style="height:46%"></span>
                        <span style="height:58%"></span>
                        <span style="height:72%"></span>
                        <span style="height:86%"></span>
                    </div>
                </article>

                <article class="card metric-card reveal">
                    <div>
                        <div class="metric-number"><span data-counter="3">0</span></div>
                        <h3>Formas de entrada</h3>
                        <p>Câmera, arquivos e arquitetura preparada para áudio/texto.</p>
                    </div>
                    <div class="mini-bars" aria-hidden="true">
                        <span style="height:48%"></span>
                        <span style="height:76%"></span>
                        <span style="height:100%"></span>
                    </div>
                </article>

                <article class="card metric-card reveal">
                    <div>
                        <div class="metric-number"><span data-counter="2">0</span></div>
                        <h3>Sentidos de tradução</h3>
                        <p>LIBRAS ↔ Português como visão de evolução do produto.</p>
                    </div>
                    <div class="mini-bars" aria-hidden="true">
                        <span style="height:62%"></span>
                        <span style="height:100%"></span>
                    </div>
                </article>

                <article class="card metric-card reveal">
                    <div>
                        <div class="metric-number"><span data-counter="24">0</span>/7</div>
                        <h3>Acesso planejado</h3>
                        <p>Experiência web pensada para estar disponível quando a comunicação for necessária.</p>
                    </div>
                    <div class="mini-bars" aria-hidden="true">
                        <span style="height:70%"></span>
                        <span style="height:75%"></span>
                        <span style="height:82%"></span>
                        <span style="height:92%"></span>
                        <span style="height:100%"></span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 6 — IMPACTO
         ========================================================= -->
    <section id="impacto" class="section-pad">
        <div class="container-page">
            <div class="section-kicker reveal">Impacto</div>

            <h2 class="section-title reveal">
                Quando a tecnologia funciona,
                <span class="gradient-text">a pessoa deixa de pedir permissão para participar.</span>
            </h2>

            <p class="section-copy reveal">
                Os exemplos abaixo são cenários de impacto — não depoimentos atribuídos a pessoas reais.
                Eles mostram onde a proposta do LibrasHub pode devolver autonomia.
            </p>

            <div class="impact-grid">
                <article class="card impact-card main reveal">
                    <div>
                        <span class="scenario-tag">Cenário de impacto · Estudante</span>
                        <div class="quote-mark">“</div>
                        <blockquote>
                            “Na sala de aula, eu não quero depender de alguém estar disponível para entender
                            uma explicação simples. Quero acompanhar a conversa no meu ritmo.”
                        </blockquote>
                        <p>
                            Um tradutor acessível pode reduzir atrasos de comunicação, apoiar estudos e
                            permitir que o aluno participe de perguntas, apresentações e atividades com mais independência.
                        </p>
                    </div>

                    <div class="persona">
                        <div class="avatar">E</div>
                        <div>
                            <strong>Estudante</strong>
                            <span>Educação e autonomia</span>
                        </div>
                    </div>
                </article>

                <article class="card impact-card reveal">
                    <span class="scenario-tag">Profissional</span>
                    <blockquote>
                        “Uma entrevista de emprego não deveria começar com a dúvida: ‘como vamos conversar?’”
                    </blockquote>
                    <p>
                        Acessibilidade também é oportunidade, empregabilidade e independência financeira.
                    </p>
                    <div class="persona">
                        <div class="avatar">P</div>
                        <div>
                            <strong>Profissional</strong>
                            <span>Trabalho e carreira</span>
                        </div>
                    </div>
                </article>

                <article class="card impact-card reveal">
                    <span class="scenario-tag">Família</span>
                    <blockquote>
                        “Entender uma orientação médica na hora certa pode mudar completamente uma decisão.”
                    </blockquote>
                    <p>
                        Saúde exige clareza. A tecnologia assistiva pode apoiar conversas em momentos de urgência e cuidado.
                    </p>
                    <div class="persona">
                        <div class="avatar">F</div>
                        <div>
                            <strong>Família</strong>
                            <span>Saúde e cuidado</span>
                        </div>
                    </div>
                </article>

                <article class="card impact-card reveal">
                    <span class="scenario-tag">Empreendedor</span>
                    <blockquote>
                        “Quero atender clientes, negociar e resolver problemas sem transformar minha surdez em uma barreira comercial.”
                    </blockquote>
                    <p>
                        Comunicação acessível amplia mercado, relacionamento e presença profissional.
                    </p>
                </article>

                <article class="card impact-card reveal">
                    <span class="scenario-tag">Comunidade</span>
                    <blockquote>
                        “A inclusão fica mais forte quando a tecnologia escuta quem realmente vai usá-la.”
                    </blockquote>
                    <p>
                        A comunidade não é só público-alvo: ela deve participar da evolução do produto.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 7 — EQUIPE
         ========================================================= -->
    <section id="equipe" class="section-pad">
        <div class="container-page">
            <div class="section-kicker reveal">Equipe</div>

            <h2 class="section-title reveal">
                Pessoas construindo tecnologia
                <span class="gradient-text">com propósito.</span>
            </h2>

            <p class="section-copy reveal">
                A equipe combina desenvolvimento, inteligência artificial, experiência do usuário e pesquisa
                em acessibilidade. Substitua os nomes abaixo pelos integrantes oficiais do seu TCC/projeto.
            </p>

            <!--
                IMPORTANTE:
                Troque "Integrante 01/02/03/04", links e textos pelos membros reais.
                O layout já está pronto para foto, LinkedIn e GitHub.
            -->
            <div class="team-grid">
                <article class="card team-card reveal">
                    <div class="team-photo" aria-hidden="true">01</div>
                    <h3>Integrante 01</h3>
                    <div class="team-role">Front-end & UI/UX</div>
                    <p>Transforma requisitos de acessibilidade em interfaces simples, responsivas e humanas.</p>
                    <div class="team-socials">
                        <a href="#" aria-label="LinkedIn do Integrante 01"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" aria-label="GitHub do Integrante 01"><i class="fa-brands fa-github"></i></a>
                    </div>
                </article>

                <article class="card team-card reveal">
                    <div class="team-photo" aria-hidden="true">02</div>
                    <h3>Integrante 02</h3>
                    <div class="team-role">IA & Visão Computacional</div>
                    <p>Trabalha na identificação de sinais, processamento e evolução do modelo de tradução.</p>
                    <div class="team-socials">
                        <a href="#" aria-label="LinkedIn do Integrante 02"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" aria-label="GitHub do Integrante 02"><i class="fa-brands fa-github"></i></a>
                    </div>
                </article>

                <article class="card team-card reveal">
                    <div class="team-photo" aria-hidden="true">03</div>
                    <h3>Integrante 03</h3>
                    <div class="team-role">Back-end & Dados</div>
                    <p>Garante autenticação, histórico, integrações e a base técnica que conecta os módulos.</p>
                    <div class="team-socials">
                        <a href="#" aria-label="LinkedIn do Integrante 03"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" aria-label="GitHub do Integrante 03"><i class="fa-brands fa-github"></i></a>
                    </div>
                </article>

                <article class="card team-card reveal">
                    <div class="team-photo" aria-hidden="true">04</div>
                    <h3>Integrante 04</h3>
                    <div class="team-role">Pesquisa & Acessibilidade</div>
                    <p>Ajuda a manter decisões do produto alinhadas à inclusão, testes e necessidades reais.</p>
                    <div class="team-socials">
                        <a href="#" aria-label="LinkedIn do Integrante 04"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" aria-label="GitHub do Integrante 04"><i class="fa-brands fa-github"></i></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- =========================================================
         TELA 8 — APOIE O PROJETO
         ========================================================= -->
    <section id="apoie" class="section-pad">
        <div class="container-page support-wrap">
            <div class="section-kicker reveal">Apoie o projeto</div>

            <h2 class="section-title reveal">
                Uma inovação social só escala quando
                <span style="color:#67e8f9;">mais pessoas decidem carregá-la junto.</span>
            </h2>

            <p class="section-copy reveal">
                O LibrasHub quer transformar tecnologia assistiva em acesso real. Para evoluir o modelo,
                ampliar o dicionário de sinais, validar com a comunidade e preparar infraestrutura de produção,
                o projeto precisa de parceiros, patrocinadores e pessoas que acreditam que comunicação é direito.
            </p>

            <div class="support-grid">

                <!-- 1. Patrocínio corporativo -->
                <article class="support-card featured reveal">
                    <div class="support-icon">
                        <i class="fa-solid fa-building-circle-check"></i>
                    </div>

                    <h3>Quero ser Patrocinador / Parceiro Corporativo</h3>

                    <p>
                        Para empresas que desejam fortalecer iniciativas de ESG, acessibilidade, inovação social,
                        educação e inclusão de pessoas com deficiência.
                    </p>

                    <div class="support-list">
                        <span><i class="fa-solid fa-check"></i> Possibilidade de parceria institucional e tecnológica.</span>
                        <span><i class="fa-solid fa-check"></i> Apoio à evolução do protótipo e testes com usuários.</span>
                        <span><i class="fa-solid fa-check"></i> Associação da marca a uma iniciativa de impacto social.</span>
                    </div>

                    <form class="sponsor-form" id="sponsorForm">
                        <div>
                            <label for="empresa">Empresa</label>
                            <input id="empresa" name="empresa" type="text" placeholder="Nome da empresa" required>
                        </div>

                        <div>
                            <label for="contatoNome">Responsável</label>
                            <input id="contatoNome" name="nome" type="text" placeholder="Seu nome" required>
                        </div>

                        <div class="full">
                            <label for="contatoEmail">E-mail corporativo</label>
                            <input id="contatoEmail" name="email" type="email" placeholder="nome@empresa.com" required>
                        </div>

                        <div class="full">
                            <label for="mensagemParceria">Como sua organização deseja apoiar?</label>
                            <textarea id="mensagemParceria" name="mensagem" placeholder="Conte brevemente sobre a oportunidade de parceria."></textarea>
                        </div>

                        <div class="full">
                            <button class="btn-light support-cta" type="submit">
                                <i class="fa-solid fa-handshake"></i>
                                Quero conversar sobre parceria
                            </button>
                        </div>
                    </form>

                    <div class="support-small">
                        O formulário encaminha o interesse para a Central de Ajuda do próprio LibrasHub,
                        preservando os caminhos atuais do projeto.
                    </div>
                </article>

                <!-- 2. Apoio coletivo -->
                <article class="support-card reveal" id="apoio-coletivo">
                    <div class="support-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>

                    <h3>Doação / Apoio Coletivo</h3>

                    <p>
                        Pequenas contribuições ajudam a financiar hospedagem, infraestrutura, coleta de dados,
                        testes, documentação e evolução contínua.
                    </p>

                    <div class="support-list">
                        <span><i class="fa-solid fa-bolt"></i> Apoio individual por Pix ou cartão.</span>
                        <span><i class="fa-solid fa-chart-line"></i> Recursos destinados ao desenvolvimento.</span>
                        <span><i class="fa-solid fa-people-group"></i> Fortalecimento de um produto com impacto social.</span>
                    </div>

                    <!--
                        Quando você tiver um link real de Pix/checkout:
                        troque o href abaixo pelo link do gateway (Mercado Pago, Stripe etc.).
                    -->
                    <a
                        class="btn-light support-cta"
                        href="templates/ajuda.php?assunto=apoio-coletivo"
                    >
                        <i class="fa-solid fa-hand-holding-heart"></i>
                        Quero apoiar o LibrasHub
                    </a>

                    <div class="support-small">
                        Conecte este botão ao seu checkout/Pix quando a forma de arrecadação oficial estiver definida.
                    </div>
                </article>

                <!-- 3. PDF comercial -->
                <article class="support-card reveal">
                    <div class="support-icon">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>

                    <h3>Edital / Apresentação Comercial</h3>

                    <p>
                        Tenha uma visão objetiva do problema, solução, tecnologia, impacto, roadmap
                        e possibilidades de parceria com o projeto.
                    </p>

                    <div class="support-list">
                        <span><i class="fa-solid fa-file-lines"></i> Material para patrocinadores e editais.</span>
                        <span><i class="fa-solid fa-briefcase"></i> Pronto para reuniões institucionais.</span>
                        <span><i class="fa-solid fa-download"></i> Download direto em PDF.</span>
                    </div>

                    <!--
                        Coloque o PDF final neste caminho:
                        /static/docs/apresentacao-comercial-librashub.pdf
                    -->
                    <a
                        class="btn-light support-cta"
                        href="static/docs/apresentacao-comercial-librashub.pdf"
                        download
                    >
                        <i class="fa-solid fa-download"></i>
                        Baixar apresentação em PDF
                    </a>

                    <div class="support-small">
                        Se o arquivo ainda não existir, crie a pasta <strong>static/docs</strong> e coloque o PDF com esse nome.
                    </div>
                </article>
            </div>

            <div class="support-manifesto reveal">
                <strong>
                    Acessibilidade não é um recurso extra. É a diferença entre estar presente e conseguir participar.
                    Se sua empresa, instituição ou comunidade acredita nisso, existe espaço para construir junto.
                </strong>

                <a class="btn-light" href="templates/ajuda.php?assunto=parceria">
                    Falar com o projeto
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- =============================================================
     RODAPÉ
     ============================================================= -->
<footer>
    <div class="container-page">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="index.php">
                    <img src="static/images/librashub-logo.png" alt="">
                    <strong>Libras<span>Hub</span></strong>
                </a>

                <p>
                    Tecnologia assistiva, tradução de LIBRAS e comunidade em uma experiência criada
                    para reduzir barreiras e ampliar autonomia.
                </p>

                <div class="footer-social">
                    <a
                        href="https://www.instagram.com/simple.prism?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Instagram"
                    >
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Plataforma</h4>
                <a href="templates/leitor.php">Tradução ao vivo</a>
                <a href="templates/upload.php">Upload</a>
                <a href="templates/login.php">Entrar</a>
                <a href="templates/cadastro.php">Criar conta</a>
            </div>

            <div class="footer-col">
                <h4>Institucional</h4>
                <a href="#tecnologia">Tecnologia</a>
                <a href="#impacto">Impacto</a>
                <a href="#equipe">Equipe</a>
                <a href="#apoie">Apoie o projeto</a>
            </div>

            <div class="footer-col">
                <h4>Suporte</h4>
                <a href="templates/ajuda.php">Central de Ajuda</a>
                <a href="templates/ajuda.php?assunto=parceria">Parcerias</a>
                <a href="static/docs/apresentacao-comercial-librashub.pdf" download>Apresentação PDF</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?= date("Y") ?> LibrasHub. Todos os direitos reservados.</span>
            <span>Tecnologia com propósito para uma comunicação mais acessível.</span>
        </div>
    </div>
</footer>

<!-- =============================================================
     ESPAÇO RESERVADO PARA VLibras / Hand Talk
     ============================================================= -->
<div
    class="libras-widget-slot"
    id="librasWidgetSlot"
    role="note"
    aria-label="Espaço reservado para widget de tradução em LIBRAS"
    title="Integração preparada para VLibras ou Hand Talk"
>
    <i class="fa-solid fa-hands-asl-interpreting" aria-hidden="true"></i>
    <span>Espaço para VLibras / Hand Talk</span>
</div>

<!-- =============================================================
     ACESSIBILIDADE
     ============================================================= -->
<div class="a11y-wrap">
    <div class="a11y-panel" id="a11yPanel">
        <h3>Acessibilidade</h3>

        <div class="a11y-row">
            <span>Modo escuro</span>
            <button class="a11y-toggle" id="darkToggle" type="button" aria-pressed="false">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>

        <div class="a11y-row">
            <span>Tamanho da fonte</span>
            <div class="a11y-mini">
                <button type="button" data-font="pequena" aria-label="Fonte pequena">A−</button>
                <button type="button" data-font="media" class="active" aria-label="Fonte média">A</button>
                <button type="button" data-font="grande" aria-label="Fonte grande">A+</button>
            </div>
        </div>

        <div class="a11y-row">
            <span>Alto contraste</span>
            <button class="a11y-toggle" id="contrastToggle" type="button" aria-pressed="false">
                <i class="fa-solid fa-circle-half-stroke"></i>
            </button>
        </div>

        <div class="a11y-row">
            <span>Leitura em voz alta</span>
            <button class="a11y-toggle" id="ttsToggle" type="button" aria-pressed="false">
                <i class="fa-solid fa-volume-high"></i>
            </button>
        </div>
    </div>

    <button
        class="a11y-fab"
        id="a11yFab"
        type="button"
        aria-label="Abrir opções de acessibilidade"
        aria-expanded="false"
        aria-controls="a11yPanel"
    >
        <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
    </button>
</div>

<script>
    /* =========================================================
       PREFERÊNCIAS DE ACESSIBILIDADE
       Mantém as mesmas chaves usadas nas outras páginas do LibrasHub.
       ========================================================= */

    const PREF_KEYS = {
        theme: 'libras_theme',
        fontSize: 'libras_fontsize',
        contrast: 'libras_contrast'
    };

    const FONT_SCALES = {
        pequena: .90,
        media: 1,
        grande: 1.15
    };

    function safeGet(key, fallback) {
        try {
            const value = localStorage.getItem(key);
            return value !== null ? value : fallback;
        } catch (error) {
            return fallback;
        }
    }

    function safeSet(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (error) {}
    }

    function applyTheme(theme) {
        const html = document.documentElement;
        const dark = theme === 'escuro';

        html.toggleAttribute('data-theme', dark);

        if (dark) {
            html.setAttribute('data-theme', 'dark');
        } else {
            html.removeAttribute('data-theme');
        }

        const button = document.getElementById('darkToggle');

        if (button) {
            button.classList.toggle('active', dark);
            button.setAttribute('aria-pressed', String(dark));
        }
    }

    function applyFontSize(size) {
        const scale = FONT_SCALES[size] || 1;

        document.documentElement.style.setProperty(
            '--font-scale',
            scale
        );

        document.querySelectorAll('[data-font]').forEach(button => {
            button.classList.toggle(
                'active',
                button.dataset.font === size
            );
        });
    }

    function applyContrast(value) {
        const enabled = value === 'on';

        document.documentElement.classList.toggle(
            'high-contrast',
            enabled
        );

        const button = document.getElementById('contrastToggle');

        if (button) {
            button.classList.toggle('active', enabled);
            button.setAttribute('aria-pressed', String(enabled));
        }
    }

    applyTheme(
        safeGet(
            PREF_KEYS.theme,
            'claro'
        )
    );

    applyFontSize(
        safeGet(
            PREF_KEYS.fontSize,
            'media'
        )
    );

    applyContrast(
        safeGet(
            PREF_KEYS.contrast,
            'off'
        )
    );

    document.getElementById('darkToggle')?.addEventListener(
        'click',
        function () {

            const current =
                safeGet(
                    PREF_KEYS.theme,
                    'claro'
                );

            const next =
                current === 'escuro'
                    ? 'claro'
                    : 'escuro';

            safeSet(
                PREF_KEYS.theme,
                next
            );

            applyTheme(
                next
            );
        }
    );

    document.querySelectorAll('[data-font]').forEach(
        button => {

            button.addEventListener(
                'click',
                function () {

                    const size =
                        this.dataset.font;

                    safeSet(
                        PREF_KEYS.fontSize,
                        size
                    );

                    applyFontSize(
                        size
                    );
                }
            );
        }
    );

    document.getElementById('contrastToggle')?.addEventListener(
        'click',
        function () {

            const current =
                safeGet(
                    PREF_KEYS.contrast,
                    'off'
                );

            const next =
                current === 'on'
                    ? 'off'
                    : 'on';

            safeSet(
                PREF_KEYS.contrast,
                next
            );

            applyContrast(
                next
            );
        }
    );

    /* =========================================================
       MENU MOBILE
       ========================================================= */

    const hamburger =
        document.getElementById(
            'hamburger'
        );

    const mobileNav =
        document.getElementById(
            'mobileNav'
        );

    function setMobileMenu(open) {

        if (
            !hamburger ||
            !mobileNav
        ) {
            return;
        }

        mobileNav.classList.toggle(
            'open',
            open
        );

        hamburger.setAttribute(
            'aria-expanded',
            String(open)
        );

        hamburger.setAttribute(
            'aria-label',
            open
                ? 'Fechar menu'
                : 'Abrir menu'
        );

        hamburger.innerHTML =
            open
                ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
                : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';

        document.body.style.overflow =
            open
                ? 'hidden'
                : '';
    }

    hamburger?.addEventListener(
        'click',
        function () {

            setMobileMenu(
                !mobileNav.classList.contains(
                    'open'
                )
            );
        }
    );

    document.querySelectorAll('[data-mobile-link]').forEach(
        link => {

            link.addEventListener(
                'click',
                function () {

                    setMobileMenu(
                        false
                    );
                }
            );
        }
    );

    window.addEventListener(
        'resize',
        function () {

            if (
                window.innerWidth > 1080
            ) {
                setMobileMenu(
                    false
                );
            }
        }
    );

    /* =========================================================
       NAVBAR: ESTADO ATIVO NO SCROLL
       ========================================================= */

    const navAnchors =
        Array.from(
            document.querySelectorAll(
                '.nav-links a[href^="#"]'
            )
        );

    const observedSections =
        navAnchors
            .map(
                link =>
                    document.querySelector(
                        link.getAttribute(
                            'href'
                        )
                    )
            )
            .filter(
                Boolean
            );

    const navObserver =
        new IntersectionObserver(
            entries => {

                const visible =
                    entries
                        .filter(
                            entry =>
                                entry.isIntersecting
                        )
                        .sort(
                            (a, b) =>
                                b.intersectionRatio
                                -
                                a.intersectionRatio
                        )[0];

                if (
                    !visible
                ) {
                    return;
                }

                navAnchors.forEach(
                    link => {

                        link.classList.toggle(
                            'active',
                            link.getAttribute(
                                'href'
                            )
                            ===
                            `#${visible.target.id}`
                        );
                    }
                );
            },
            {
                rootMargin:
                    '-28% 0px -58% 0px',
                threshold:
                    [0.05, .2, .45]
            }
        );

    observedSections.forEach(
        section =>
            navObserver.observe(
                section
            )
    );

    /* =========================================================
       REVEAL / SCROLL ANIMATIONS
       ========================================================= */

    const revealObserver =
        new IntersectionObserver(
            entries => {

                entries.forEach(
                    entry => {

                        if (
                            entry.isIntersecting
                        ) {
                            entry.target.classList.add(
                                'visible'
                            );

                            revealObserver.unobserve(
                                entry.target
                            );
                        }
                    }
                );
            },
            {
                threshold: .12
            }
        );

    document.querySelectorAll(
        '.reveal'
    ).forEach(
        element =>
            revealObserver.observe(
                element
            )
    );

    /* =========================================================
       CONTADORES
       ========================================================= */

    function animateCounter(element) {

        const target =
            Number(
                element.dataset.counter
            );

        if (
            !Number.isFinite(
                target
            )
        ) {
            return;
        }

        const duration =
            900;

        const start =
            performance.now();

        function frame(now) {

            const progress =
                Math.min(
                    1,
                    (
                        now - start
                    )
                    /
                    duration
                );

            const eased =
                1
                -
                Math.pow(
                    1 - progress,
                    3
                );

            element.textContent =
                Math.round(
                    target
                    *
                    eased
                );

            if (
                progress < 1
            ) {
                requestAnimationFrame(
                    frame
                );
            }
        }

        requestAnimationFrame(
            frame
        );
    }

    const counterObserver =
        new IntersectionObserver(
            entries => {

                entries.forEach(
                    entry => {

                        if (
                            entry.isIntersecting
                        ) {
                            animateCounter(
                                entry.target
                            );

                            counterObserver.unobserve(
                                entry.target
                            );
                        }
                    }
                );
            },
            {
                threshold: .45
            }
        );

    document.querySelectorAll(
        '[data-counter]'
    ).forEach(
        counter =>
            counterObserver.observe(
                counter
            )
    );

    /* =========================================================
       ACESSIBILIDADE
       ========================================================= */

    const a11yFab =
        document.getElementById(
            'a11yFab'
        );

    const a11yPanel =
        document.getElementById(
            'a11yPanel'
        );

    a11yFab?.addEventListener(
        'click',
        function () {

            const open =
                !a11yPanel.classList.contains(
                    'open'
                );

            a11yPanel.classList.toggle(
                'open',
                open
            );

            a11yFab.setAttribute(
                'aria-expanded',
                String(open)
            );
        }
    );

    document.addEventListener(
        'click',
        function (event) {

            const wrap =
                document.querySelector(
                    '.a11y-wrap'
                );

            if (
                wrap &&
                !wrap.contains(
                    event.target
                )
            ) {
                a11yPanel?.classList.remove(
                    'open'
                );

                a11yFab?.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }
        }
    );

    /* =========================================================
       TEXT-TO-SPEECH
       ========================================================= */

    let speaking =
        false;

    const ttsButton =
        document.getElementById(
            'ttsToggle'
        );

    function stopSpeech() {

        if (
            'speechSynthesis'
            in window
        ) {
            window.speechSynthesis.cancel();
        }

        speaking =
            false;

        ttsButton?.classList.remove(
            'active'
        );

        ttsButton?.setAttribute(
            'aria-pressed',
            'false'
        );
    }

    ttsButton?.addEventListener(
        'click',
        function () {

            if (
                !(
                    'speechSynthesis'
                    in window
                )
            ) {
                alert(
                    'Seu navegador não oferece suporte à leitura em voz alta.'
                );

                return;
            }

            if (
                speaking
            ) {
                stopSpeech();
                return;
            }

            const text =
                document
                    .getElementById(
                        'conteudo'
                    )
                    .innerText
                    .replace(
                        /\s+/g,
                        ' '
                    )
                    .trim();

            const speech =
                new SpeechSynthesisUtterance(
                    text
                );

            speech.lang =
                'pt-BR';

            speech.rate =
                .94;

            speech.onend =
                stopSpeech;

            speech.onerror =
                stopSpeech;

            speaking =
                true;

            ttsButton.classList.add(
                'active'
            );

            ttsButton.setAttribute(
                'aria-pressed',
                'true'
            );

            window.speechSynthesis.speak(
                speech
            );
        }
    );

    /* =========================================================
       FORMULÁRIO DE PATROCÍNIO
       Conecta ao caminho já existente: templates/ajuda.php
       ========================================================= */

    document.getElementById(
        'sponsorForm'
    )?.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();

            const formData =
                new FormData(
                    this
                );

            const params =
                new URLSearchParams();

            params.set(
                'assunto',
                'parceria'
            );

            [
                'empresa',
                'nome',
                'email',
                'mensagem'
            ].forEach(
                key => {

                    const value =
                        String(
                            formData.get(
                                key
                            )
                            ??
                            ''
                        ).trim();

                    if (
                        value
                    ) {
                        params.set(
                            key,
                            value
                        );
                    }
                }
            );

            window.location.href =
                'templates/ajuda.php?'
                +
                params.toString();
        }
    );

    /* =========================================================
       ESC PARA FECHAR ELEMENTOS
       ========================================================= */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key !== 'Escape'
            ) {
                return;
            }

            setMobileMenu(
                false
            );

            a11yPanel?.classList.remove(
                'open'
            );

            a11yFab?.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    );

    window.addEventListener(
        'beforeunload',
        stopSpeech
    );
</script>

</body>
</html>
