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
        cy.loginByCookies()
        cy.runRoutes()
    })
    const resizeObserverLoopErrRe = /^[^(ResizeObserver loop limit exceeded)]/
    Cypress.on('uncaught:exception', (err) => {
        /* returning false here prevents Cypress from failing the test */
        if (resizeObserverLoopErrRe.test(err.message)) {
            return false
        }
    })

    it('Should create CorpLine Quotes', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/quotes/business/create')
        commonPage.verifyURL('/quotes/business/create')
        commonPage.getFirstNameField('Muhammad')
        commonPage.getLastNameField('Abdullah')
        commonPage.getEmail('qa_automation@myalfred.com')
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
        cy.url().then(data => {
            cy.log("Data logged", data)
            leadUrl = `/${data.substring(data.lastIndexOf('quotes/health/'))}`
            quoteId = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log(leadUrl)
            cy.log(quoteId)
        })

    })

    it('Should Edit CorpLine Quotes details, Update Lead Status, Add/Delete Activity, Add Additional Contact', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(`quotes/business/${quoteId}`)
        commonPage.verifyURL('/quotes/business')
        corpLinePage.getEditButton().click()
        commonPage.getFirstNameField('Muhammad updated')
        commonPage.getLastNameField('Abdullah updated')
        corpLinePage.getCompanyNameField('Test Company Updated')
        corpLinePage.getPolicyNumberField('Null')
        corpLinePage.getPremiumField().type('6')
        corpLinePage.getNumberOfEmployeeField('12')
        corpLinePage.getBusinessInsuranceType('Group Life')
        corpLinePage.getBriefDetailsScreen().type(quoteData.personalInfo.dummyText)
        corpLinePage.getGender().select('Male')
        commonPage.getButtonByName('Update')
        commonPage.getPopUpAssertion('Business has been updated')

        //Update Lead Status
        corpLinePage.getLeadStatusDropdown('New Lead')
        corpLinePage.getNotesField().type(quoteData.personalInfo.dummyText)
        commonPage.getButtonByName('Change Status')

        //Lead Activity
        corpLinePage.getAddActivityButton().click()
        corpLinePage.getActivityTitleField().type('Test Title')
        corpLinePage.getActivityDescriptionField().type(quoteData.personalInfo.dummyText)
        corpLinePage.getActivityAssignee().select('Muhammad Ali Riaz - AMT_ADVISOR')
        commonPage.getButtonByName('Add Activity')
        commonPage.getPopUpAssertion(' Activity has been Created')

        //Delete Activity
        corpLinePage.getDeleteActivityButton()


        //Add Additional Contact
        corpLinePage.getAdditionalContact().click()
        corpLinePage.getContactType().select('Email')
        corpLinePage.getValueField().type('test@gmail.com')

    })
})
