# SEO Implementation Guide - نرخ‌نامه قیمت

## Overview
This document describes the comprehensive SEO improvements implemented for the Price Tracker application.

## 1. Meta Tags & Head Management

### Files Modified:
- **resources/views/app.blade.php**: Added comprehensive meta tags including:
  - Primary meta tags (title, description, keywords)
  - Open Graph tags for social media
  - Twitter Card tags
  - Canonical URL tags
  - Structured data (JSON-LD)
  - Security headers

### SeoHead Component:
- **Location**: `resources/js/Components/SeoHead.vue`
- **Purpose**: Reusable component for managing page-specific meta tags
- **Features**:
  - Dynamic title and description
  - Open Graph support
  - Twitter Card support
  - Canonical URL handling
  - No-index support for private pages
  - JSON-LD structured data

### Usage Example:
```vue
<SeoHead
    title="لیست قیمت محصولات"
    description="قیمت روز میوه و سبزیجات..."
    keywords="قیمت میوه, قیمت سبزیجات..."
    :schema="{...}"
/>
```

## 2. Structured Data (Schema.org)

### JSON-LD Implementation:
- **Organization Schema**: `app.blade.php` - Defines the website organization
- **Website Schema**: Enables search engine site search box features
- **Product Schema**: Implemented in `SeoHead.vue` for product pages
- **Breadcrumb Schema**: Available in `resources/js/Utils/seoSchemas.js`
- **FAQ Schema**: Available in `resources/js/Utils/seoSchemas.js`
- **LocalBusiness Schema**: Available in `resources/js/Utils/seoSchemas.js`

### Schema Files:
- **Location**: `resources/js/Utils/seoSchemas.js`
- **Helpers**:
  - `generateProductSchema(product)`: Creates product schema
  - `generateBreadcrumbSchema(items)`: Creates breadcrumb navigation
  - `generateFAQSchema(faqs)`: Creates FAQ page schema
  - `generateLocalBusinessSchema()`: Creates local business schema

## 3. Robots.txt & Sitemap

### Robots.txt:
- **Location**: `public/robots.txt`
- **Features**:
  - Allows general crawling
  - Disallows admin and private paths
  - Allows crawling of static assets
  - Crawl delay specifications
  - Search engine specific rules
  - Bad bot blocking

### Sitemap:
- **Location**: `public/sitemap.xml`
- **Features**:
  - URL list with priority and frequency
  - Image sitemap support
  - Update frequency indicators
  - Lastmod timestamp

### Update Instructions:
```bash
# Register sitemap in Google Search Console:
# https://search.google.com/search-console
# Add sitemap: https://your-domain.com/sitemap.xml
```

## 4. Performance Optimization

### .htaccess Improvements:
- **Location**: `public/.htaccess`
- **Features**:
  - GZIP compression for text assets
  - Browser caching with proper expire headers
  - Cache-Control headers
  - Security headers:
    - X-Content-Type-Options: nosniff
    - X-Frame-Options: SAMEORIGIN
    - X-XSS-Protection: 1; mode=block
    - Referrer-Policy: strict-origin-when-cross-origin
    - Permissions-Policy: Disabled cameras, microphones, geolocation
  - ETag removal for improved performance
  - UTF-8 encoding specification
  - MIME type definitions

### Caching Strategy:
- **Images**: 1 year
- **CSS/JavaScript**: 1 month
- **Fonts**: 1 year
- **HTML**: 1 hour
- **Default**: 1 hour

## 5. Image Optimization

### Alt Text:
All images now have descriptive alt text:
```vue
<img :src="image" :alt="`تصویر ${productName}`" :title="productName" />
```

### Image Assets:
- **Product Images**: SVG format for scalability
  - `public/storage/products/cucumber.svg`
  - `public/storage/products/tomato.svg`
  - `public/storage/products/pepper.svg`
  - `public/storage/products/onion.svg`

- **OG Image**: `public/og-image.svg` for social sharing

## 6. Frontend Page Updates

### PriceList.vue:
- Added SeoHead component for dynamic meta tags
- Comprehensive schema.org ProductCollection markup
- Image alt text for all product images
- Proper heading hierarchy (h1, h3)
- Semantic HTML structure

### Dashboard.vue:
- Added SEO head component
- Set noindex=true for admin pages
- Proper meta descriptions

### App.js:
- Preconnect and DNS-prefetch for fonts
- Dynamic resource loading optimization

## 7. Mobile & Responsive SEO

### Mobile Optimization:
- Viewport meta tag with viewport-fit=cover
- Responsive image sizing (srcset-ready)
- Mobile-first CSS approach
- Touch-friendly UI

### Core Web Vitals:
- Lazy loading for images
- Optimized font loading
- Minimized CSS/JS bundles
- GZIP compression enabled

## 8. Semantic HTML

### Improvements Made:
- Proper heading hierarchy (h1 for main title)
- Semantic HTML elements:
  - `<header>` for page headers
  - `<main>` for main content
  - `<article>` for content blocks
  - `<nav>` for navigation
  - `<section>` for content sections

### Language Tags:
- HTML lang="fa" for Persian
- dir="rtl" for right-to-left text

## 9. Environment Configuration

### SEO Config Variables (.env):
```dotenv
SEO_DESCRIPTION="قیمت محصولات..."
SEO_KEYWORDS="قیمت میوه, قیمت سبزیجات..."
SEO_OG_IMAGE="/og-image.svg"
SEO_TWITTER_HANDLE="@your_handle"
SITE_DOMAIN="your-domain.com"
```

## 10. Best Practices Implemented

### On-Page SEO:
✅ Unique title tags for each page
✅ Meta descriptions with target keywords
✅ Proper heading hierarchy
✅ Descriptive alt text for images
✅ Internal linking structure
✅ URL structure optimization

### Technical SEO:
✅ XML Sitemap
✅ Robots.txt
✅ Canonical URLs
✅ Mobile responsive design
✅ Fast page load times
✅ GZIP compression
✅ Browser caching
✅ Security headers

### Content SEO:
✅ Open Graph tags
✅ Twitter Card tags
✅ Schema.org markup
✅ Semantic HTML
✅ Proper language tags

### Off-Page SEO:
✅ Social media integration ready
✅ Share-friendly metadata
✅ Social card previews

## 11. Search Engine Verification

### Google Search Console:
```
1. Go to: https://search.google.com/search-console
2. Add property: https://your-domain.com
3. Verify ownership (HTML file, DNS, Meta tag)
4. Submit sitemap: https://your-domain.com/sitemap.xml
5. Monitor crawl errors and indexation
```

### Bing Webmaster Tools:
```
1. Go to: https://www.bing.com/webmasters
2. Add site: https://your-domain.com
3. Submit sitemap
4. Monitor health and keywords
```

## 12. Analytics Integration

### Recommended Tools:
```html
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>

<!-- Add to: resources/views/app.blade.php -->
```

## 13. Performance Checklist

- [ ] Replace "your-domain.com" with actual domain
- [ ] Update SEO_OG_IMAGE path to accessible URL
- [ ] Update SITE_DOMAIN in .env
- [ ] Create high-quality OG image (1200x630px)
- [ ] Add Google Analytics tracking code
- [ ] Add Google Search Console verification
- [ ] Add Bing Webmaster Tools verification
- [ ] Test with Google PageSpeed Insights
- [ ] Test with Lighthouse
- [ ] Review Core Web Vitals
- [ ] Setup Google My Business (if local business)

## 14. Monitoring & Maintenance

### Monthly Tasks:
- Check Google Search Console for errors
- Review crawl statistics
- Monitor Core Web Vitals
- Update sitemap with new pages
- Check search rankings

### Quarterly Tasks:
- Audit backlinks
- Review competitor SEO strategies
- Update schema markup
- Optimize underperforming pages
- Review traffic patterns

## 15. Useful Resources

### SEO Tools:
- [Google Search Console](https://search.google.com/search-console)
- [Google PageSpeed Insights](https://pagespeed.web.dev/)
- [Bing Webmaster Tools](https://www.bing.com/webmasters)
- [Schema.org Validator](https://validator.schema.org/)
- [SEMrush](https://www.semrush.com/)

### Persian SEO Resources:
- [Google Search Central](https://developers.google.com/search)
- [Yandex Webmaster](https://webmaster.yandex.com/) (for CIS countries)
- [Schema.org Documentation](https://schema.org/)

## Configuration Summary

| Item | Value | Location |
|------|-------|----------|
| App Name | نرخ‌نامه قیمت | .env |
| Primary Description | قیمت روز میوه و سبزیجات... | .env, app.blade.php |
| OG Image | /og-image.svg | public/ |
| Sitemap | sitemap.xml | public/ |
| Robots | robots.txt | public/ |
| Schema | Organization, Website | app.blade.php |

---

**Last Updated**: February 21, 2026
**Version**: 1.0
**Status**: Production Ready

