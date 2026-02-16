const { test, expect } = require('@playwright/test');

test.describe('WBWAN frontend', () => {
	test('renders accordion and core interactions', async ({ page }) => {
		await page.goto('/shop-navigation-demo/');
		await expect(page.locator('[data-wbwan="accordion"]')).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Shop Navigation' })).toBeVisible();

		const electronicsItem = page.locator('.wbwan-item.has-children', { hasText: 'Electronics' }).first();
		await expect(electronicsItem).toBeVisible();

		const electronicsToggle = electronicsItem.locator('[data-wbwan="toggle"]').first();
		await electronicsToggle.click();
		await expect(electronicsItem).toHaveClass(/is-open/);

		const search = page.locator('input[data-wbwan="search"]').first();
		await expect(search).toBeVisible();
		await search.fill('orbit');
		await expect(page.getByRole('link', { name: /Orbit/i }).first()).toBeVisible();
	});

	test('mobile off-canvas opens and closes', async ({ page }) => {
		await page.setViewportSize({ width: 390, height: 844 });
		await page.goto('/shop-navigation-demo/');

		const wrapper = page.locator('[data-wbwan="accordion"]').first();
		const openButton = page.locator('[data-wbwan="mobile-toggle"]').first();
		const closeButton = page.locator('[data-wbwan="mobile-close"]').first();

		await expect(openButton).toBeVisible();
		await openButton.click();
		await expect(wrapper).toHaveClass(/is-open/);
		await closeButton.click();
		await expect(wrapper).not.toHaveClass(/is-open/);
	});

	test('ajax sort updates URL and sends analytics payload', async ({ page }) => {
		await page.goto('/shop-navigation-demo/');
		const sort = page.locator('select[data-wbwan="sort"]').first();
		await expect(sort).toBeVisible();
		const analyticsEnabled = await page.evaluate(() => {
			return !!(window.wbwanSettings && window.wbwanSettings.analyticsEnabled);
		});

		const analyticsRequestPromise = analyticsEnabled
			? page.waitForRequest((request) => {
				return request.url().includes('/wp-json/wbwan/v1/analytics') && request.method() === 'POST';
			})
			: null;
		const filterResponsePromise = page.waitForResponse((response) => {
			return response.url().includes('/wp-json/wbwan/v1/filter') && response.status() === 200;
		});

		await sort.selectOption('price_desc');
		await filterResponsePromise;

		await expect(page).toHaveURL(/wbf_sort=price_desc/);
		if (analyticsRequestPromise) {
			const analyticsRequest = await analyticsRequestPromise;
			const body = analyticsRequest.postDataJSON();
			expect(body.sort).toBe('price_desc');
		}
	});
});

test.describe('WBWAN admin settings', () => {
	test('settings page loads', async ({ page }) => {
		const user = process.env.E2E_USER || 'wbwan_admin';
		const pass = process.env.E2E_PASS || 'Passw0rd!234';

		await page.goto('/wp-login.php');
		await page.getByLabel('Username or Email Address').fill(user);
		await page.locator('#user_pass').fill(pass);
		await page.getByRole('button', { name: 'Log In' }).click();

		await page.goto('/wp-admin/admin.php?page=wbwan-settings');
		await expect(page.getByRole('heading', { name: 'WB Accordion Navigation for WooCommerce' })).toBeVisible();
		await expect(page.locator('input[name="wbwan_settings[enable_search]"]')).toBeChecked();
		await expect(page.locator('input[name="wbwan_settings[mobile_offcanvas]"]')).toBeChecked();
	});
});
