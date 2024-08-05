const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    baseUrl: 'https://crmstage.alfred.ae',
    specPattern: 'cypress/e2e/**/*Spec.js',
    viewportWidth: 1920,
    viewportHeight: 1080,
    pageLoadTimeout: 100000,
    defaultCommandTimeout: 100000,
  },
});
