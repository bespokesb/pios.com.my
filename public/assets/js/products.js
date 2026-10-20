/**
 * PIOS BizGrowth Engine - Products Management Script
 * Handles real-time search filtering for products catalog
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('productSearchInput');
    const productGrid = document.getElementById('productGrid');

    if (searchInput && productGrid) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const currentCards = productGrid.querySelectorAll('.prod-card');

            currentCards.forEach(card => {
                const name = (card.getAttribute('data-name') || '').toLowerCase();
                const category = (card.getAttribute('data-category') || '').toLowerCase();
                const price = (card.getAttribute('data-price') || '').toLowerCase();

                if (name.includes(query) || category.includes(query) || price.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
