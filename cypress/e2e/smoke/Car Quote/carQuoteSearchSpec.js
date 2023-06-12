import CarLeadPage from '../../../pageObjects/CarLeadPage';
import CommonPage from '../../../pageObjects/CommonPage';
let carLeadData = require('../../../fixtures/carLeadData')
let qouteData = require('../../../fixtures/qoutesData')

describe('Car qoutes', () => {

  const commonPage = new CommonPage()
  const carLeadPage = new CarLeadPage()

  beforeEach(() => {
    cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'), Cypress.env('remember_web_token'))
    cy.runRoutes()
  })

  it('Should search car quote using different methods', () => {
    cy.visit('/quotes/car')
    //Search view using CDB ID
    carLeadPage.searchUsingCDBID(carLeadData.carLeadData.cdbId)
    carLeadPage.getSearchButton()
    carLeadPage.getResetButton()
    // Search view using first Name
    carLeadPage.searchUsingFirstname(qouteData.personalInfo.firstName)
    carLeadPage.getSearchButton()
    carLeadPage.getResetButton()
    //Search view using Last Name
    carLeadPage.searchUsingLastname(qouteData.personalInfo.lastName)
    carLeadPage.getSearchButton()
    carLeadPage.getResetButton()
    //Search view using Email
    carLeadPage.searchUsingEmail(qouteData.personalInfo.email)
    carLeadPage.getSearchButton()
    carLeadPage.getResetButton()
    //Search view using Phone Number
    carLeadPage.searchUsingMobileNumber(qouteData.personalInfo.phoneNumber)
    carLeadPage.getSearchButton()
    carLeadPage.getResetButton()
  })
})