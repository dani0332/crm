import CommonPage from '../../../pageObjects/CommonPage';
import HealthLeadPage from '../../../pageObjects/HealthLeadPage';
let quoteData = require('../../../fixtures/qoutesData.json')
let healthLeadData = require('../../../fixtures/healthLeadData.json')
describe('Health qoutes', () => {

  const commonPage = new CommonPage()
  const healthLeadPage = new HealthLeadPage()

  beforeEach(() => {
    cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'))
    cy.runRoutes()
  })
  const resizeObserverLoopErrRe = /^[^(ResizeObserver loop limit exceeded)]/
  Cypress.on('uncaught:exception', (err) => {
    /* returning false here prevents Cypress from failing the test */
    if (resizeObserverLoopErrRe.test(err.message)) {
      return false
    }
  })
  it('Should create lead car qoutes insurance', () => {
    Cypress.on('uncaught:exception', err => {
      if (resizeObserverLoopErrRe.test(err.message)) {
        return false
      }
    })
    cy.visit('/quotes/health/create')
    commonPage.verifyURL('/quotes/health/create')
    commonPage.getTextFieldByName('FIRST NAME', quoteData.personalInfo.firstName)
    commonPage.getTextFieldByName('LAST NAME ', quoteData.personalInfo.lastName)
    healthLeadPage.getEmailField(quoteData.personalInfo.email)
    healthLeadPage.getMobileNumberField(quoteData.personalInfo.phoneNumber)
    commonPage.getDateTimeDropdown()
    healthLeadPage.getYearModal()
    healthLeadPage.getYear()
    healthLeadPage.getDate()
    commonPage.getTextFieldByName('PREMIUM ', healthLeadData.healthData.premium)
    commonPage.getTextFieldByName('POLICY NUMBER ', healthLeadData.healthData.policyNumber)
    commonPage.getDropdownByName('WHO WOULD YOU LIKE COVER FOR? ', 'Individual')
    commonPage.getNationality('NATIONALITY ', 'Afghan')
    commonPage.getDropdownByName('EMIRATE OF YOUR VISA ', 'Dubai')
    commonPage.getTextFieldByName('PREFERENCE ', healthLeadData.healthData.reference)
    commonPage.getTextFieldByName('DETAILS ', healthLeadData.healthData.details)
    commonPage.getDropdownByName('LEAD TYPE ', 'Individual')
    commonPage.getDropdownByName('CURRENTLY INSURED WITH ', 'BUPA')
    commonPage.getDropdownByName('MARITAL STATUS ', 'Single')
    commonPage.getDropdownByName('MEMBER CATEGORY ', 'Employee')
    commonPage.getDropdownByName('SALARY BAND ', 'More than AED 4000')
    commonPage.getDropdownByName('GENDER ', 'Male')
    healthLeadPage.getSubmitButton()
    commonPage.verifyURL('/quotes/health')
  })
})