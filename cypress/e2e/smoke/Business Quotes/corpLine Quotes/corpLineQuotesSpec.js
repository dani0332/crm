import CommonPage from "../../../../pageObjects/CommonPage";
import CorpLinePage from "../../../../pageObjects/corpLinePage";
let quoteData = require('../../../../fixtures/qoutesData.json')
let corpLineData = require('../../../../fixtures/corpLineLeadData.json')
let leadUrl
let quoteId
describe('CorpLine qoutes', () => {

    const commonPage = new CommonPage()
    const corpLinePage = new CorpLinePage()

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

    it('Should Create CorpLine Quotes', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/quotes/business/create')
        commonPage.verifyURL('/quotes/business/create')
        commonPage.getFirstNameField('Muhammad')
        commonPage.getLastNameField('Abdullah')
        commonPage.getEmail('im.automation4@gmail.com')
        commonPage.getMobileNumber('0501234567')
        corpLinePage.getCompanyNameField('Test Company')
        corpLinePage.getPolicyNumberField('Test')
        corpLinePage.getPremiumField().type('5')
        corpLinePage.getNumberOfEmployeeField().type('10')
        corpLinePage.getBusinessInsuranceType().select('Group Medical')
        corpLinePage.getBriefDetailsScreen().type(quoteData.personalInfo.dummyText)
        corpLinePage.getGender().select('Male')
        commonPage.getButtonByName('Create')
        commonPage.getPopUpAssertion('Lead has been created')

    })
})
