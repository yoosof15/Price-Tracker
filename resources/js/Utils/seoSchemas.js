<script setup>
export const generateProductSchema = (product) => {
    return {
        '@context': 'https://schema.org',
        '@type': 'Product',
        'name': product.product_name,
        'description': `قیمت روز ${product.product_name}`,
        'image': product.image ? `${window.location.origin}/storage/${product.image}` : null,
        'brand': {
            '@type': 'Brand',
            'name': 'نرخ‌نامه قیمت'
        },
        'color': product.color || undefined,
        'offers': {
            '@type': 'AggregateOffer',
            'priceCurrency': 'IRR',
            'offers': product.prices ? Object.values(product.prices).map(price => ({
                '@type': 'Offer',
                'price': price.max_price || price.min_price,
                'availability': 'https://schema.org/InStock'
            })) : []
        }
    };
};

export const generateBreadcrumbSchema = (items) => {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        'itemListElement': items.map((item, index) => ({
            '@type': 'ListItem',
            'position': index + 1,
            'name': item.name,
            'item': item.url
        }))
    };
};

export const generateFAQSchema = (faqs) => {
    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        'mainEntity': faqs.map(faq => ({
            '@type': 'Question',
            'name': faq.question,
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': faq.answer
            }
        }))
    };
};

export const generateLocalBusinessSchema = () => {
    return {
        '@context': 'https://schema.org',
        '@type': 'LocalBusiness',
        'name': 'نرخ‌نامه قیمت',
        'description': 'نرخ‌نامه قیمت محصولات - اطلاعات بروز شده قیمت میوه و سبزیجات',
        'url': window.location.origin,
        'image': `${window.location.origin}/images/my-logo2.png`,
        'telephone': '+98-XXX-XXXX-XXXX',
        'address': {
            '@type': 'PostalAddress',
            'addressCountry': 'IR',
            'addressLocality': 'ایران'
        },
        'areaServed': {
            '@type': 'Country',
            'name': 'Iran'
        }
    };
};
</script>
