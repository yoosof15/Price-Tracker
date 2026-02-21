<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'نرخ‌نامه قیمت محصولات. اطلاعات بروز شده قیمت میوه و سبزیجات در بازارهای مختلف',
    },
    keywords: {
        type: String,
        default: 'قیمت میوه, قیمت سبزیجات, نرخ‌نامه, بازار, خیار, گوجه‌فرنگی, فلفل, پیاز',
    },
    ogImage: {
        type: String,
        default: '/og-image.jpg',
    },
    ogType: {
        type: String,
        default: 'website',
    },
    canonical: {
        type: String,
        default: null,
    },
    noindex: {
        type: Boolean,
        default: false,
    },
});

const siteUrl = computed(() => window.location.origin);
const pageUrl = computed(() => window.location.href);
</script>

<template>
    <Head>
        <title>{{ title }} - نرخ‌نامه قیمت</title>
        <meta name="description" :content="description" />
        <meta name="keywords" :content="keywords" />
        <meta name="theme-color" content="#1f2937" />
        
        <!-- Open Graph Meta Tags -->
        <meta property="og:type" :content="ogType" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:url" :content="pageUrl" />
        <meta property="og:site_name" content="نرخ‌نامه قیمت" />
        <meta property="og:locale" content="fa_IR" />
        
        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImage" />
        
        <!-- Canonical Tag -->
        <link v-if="canonical" rel="canonical" :href="canonical" />
        
        <!-- Robots Meta Tag -->
        <meta v-if="noindex" name="robots" content="noindex, nofollow" />
        <meta v-else name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        
        <!-- Language -->
        <meta name="language" content="Persian" />
        <meta name="revisit-after" content="7 days" />
        <meta name="author" content="نرخ‌نامه قیمت" />
    </Head>
</template>
