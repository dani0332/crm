// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
import 'cypress-file-upload'
Cypress.Commands.add('loginByCookies', (session, token) => {
   cy.setCookie('imcrm_session', session)
   cy.setCookie('XSRF-TOKEN', token)
})

//  Cypress.Commands.add('generate_car_CDBID', (token,data) => {
//    cy.request({
//       method: `POST`,
//       url: `${Cypress.env('Base_Url_Staging')}/api/v1-save-car-quote`,
//       headers:{
//          "x-api-token":token
//       },
//       body:data
//    }).then((response)=>{
//       expect(response.status).to.eq(200)
//       return response.body.quoteUID
//    })
// })

Cypress.Commands.add('generate_CDBID', (token, data, endPoint) => {
   cy.request({
      method: `POST`,
      url: `${Cypress.env('Base_Url_Staging')}${endPoint}`,
      headers: {
         "x-api-token": token
      },
      body: data
   }).then((response) => {
      expect(response.status).to.eq(200)
      return response.body.quoteUID
   })
})

Cypress.Commands.add('runRoutes', () => {
   cy.intercept(`GET`, `Request URL: https://crmstage.alfred.ae/quotes/car/*`).as('createLead')
   cy.intercept(`DELETE`, `/travelers/*`).as('deleteTraveler')
   cy.intercept(`DELETE`, `/travelers/*`).as('deleteActivity')
})