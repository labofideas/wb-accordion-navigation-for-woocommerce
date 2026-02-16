// @ts-check
const { defineConfig } = require('@playwright/test');

const baseURL = process.env.E2E_BASE_URL || 'http://wbrbpw.local';

module.exports = defineConfig({
	testDir: './tests/e2e',
	timeout: 45_000,
	expect: {
		timeout: 10_000,
	},
	use: {
		baseURL,
		headless: true,
		trace: 'retain-on-failure',
	},
	workers: 1,
	reporter: [['list']],
});
