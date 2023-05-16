const { defineConfig } = require("cypress");

module.exports = defineConfig({
  projectId: '7nc7wo',
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    baseUrl: 'https://crmstage.alfred.ae',
    specPattern: 'cypress/e2e/**/*Spec.js',
    viewportWidth: 1920,
    viewportHeight: 1080,
    pageLoadTimeout: 30000,
    defaultCommandTimeout: 100000,
  },

});
