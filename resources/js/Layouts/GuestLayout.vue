<template>
    <div class="relative w-full h-screen overflow-hidden">
        <div class="absolute inset-0 z-0 animated-gradient"></div>
        <div class="absolute inset-0 z-10 pointer-events-none">
            <div class="absolute top-10 left-10 w-1 h-1 bg-white rounded-full opacity-50 blur-sm star"></div>
            <div class="absolute top-1/3 left-2/3 w-1 h-1 bg-white rounded-full opacity-40 blur-sm star"></div>
            <div class="absolute bottom-1/4 right-1/4 w-1 h-1 bg-white rounded-full opacity-30 blur-sm star"></div>
            <div class="absolute top-1/2 left-1/5 w-1 h-1 bg-white rounded-full opacity-60 blur-sm star"></div>
            <div class="absolute bottom-10 left-1/3 w-1 h-1 bg-white rounded-full opacity-70 blur-sm star"></div>
        </div>

        <div class="relative z-20 flex items-center justify-center h-full">
            <div class="bg-black/60 rounded-xl shadow-xl p-10 text-white backdrop-blur-md">
                <slot />
            </div>
        </div>
    </div>
</template>
<style>
    @keyframes pulseBackground {
        0%, 100% {
            background-position: center;
        }
        50% {
            background-position: 45% 55%;
        }
    }

    .animated-gradient {
        background: radial-gradient(circle at center, #57229c, #000010);
        background-size: 200% 200%;
        animation: pulseBackground 10s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .star {
        animation: float 6s ease-in-out infinite;
    }
</style>