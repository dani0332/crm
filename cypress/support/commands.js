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
Cypress.Commands.add('loginByCookies', () => {
   cy.setCookie('imcrm_session', Cypress.env('imcrm_session'))
   cy.setCookie('XSRF-TOKEN', Cypress.env('XSRF-TOKEN'))
   cy.setCookie('remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', Cypress.env('remember_web_token'))
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
   cy.intercept(`GET`, `https://crmstage.alfred.ae/quotes/car/*`).as('createLead')
   cy.intercept(`GET`, `https://crmstage.alfred.ae/quotes/health/*`).as('createHealth')
   cy.intercept(`GET`, `https://crmstage.alfred.ae/quotes/home/*`).as('deleteHomeActivity')
   cy.intercept(`GET`, `https://crmstage.alfred.ae/quotes/life/*`).as('deleteLifeActivity')
   cy.intercept(`GET`, `https://crmstage.alfred.ae/quotes/travel/*`).as('deleteTravelMember')
})