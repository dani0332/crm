import CommonPage from '../../../pageObjects/CommonPage';
import lifePage from '../../../pageObjects/LifeLeadPage';
let quoteData = require('../../../fixtures/qoutesData')
let LifePage = require('../../../pageObjects/LifeLeadPage')
let lifeLeadData = require('../../../fixtures/lifeLeadData.json')
let leadUrl
let quoteId
describe('Life qoutes', () => {
    const commonPage = new CommonPage()
    const LifePage = new lifePage()

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
    it('Should Create Life Quote', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/quotes/life')
        commonPage.verifyURL('/quotes/life')
        LifePage.getCreateButton()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getEmail(quoteData.personalInfo.email)
        commonPage.getMobileNumber(quoteData.personalInfo.phoneNumber)
        commonPage.getDOB(quoteData.personalInfo.month, quoteData.personalInfo.year, quoteData.personalInfo.day)
        LifePage.getNationality("Pakistani")
        LifePage.getSumInsuredValue()
        LifePage.getPremiumField()
        LifePage.getCurrency(lifeLeadData.lfieLeadData.currency)
        LifePage.getPurposeInsuranceField(lifeLeadData.lfieLeadData.purpose)
        LifePage.getMartialStatus(lifeLeadData.lfieLeadData.martialStatus)
        LifePage.getChildren(lifeLeadData.lfieLeadData.children)
        LifePage.getTypeOfInsurance(lifeLeadData.lfieLeadData.InsuranceType)
        LifePage.getTenure(lifeLeadData.lfieLeadData.tenure)
        LifePage.getGender(lifeLeadData.lfieLeadData.gender)
        LifePage.getSmoker(lifeLeadData.lfieLeadData.smoker)
        // LifePage.getOthersInfo(lifeLeadData.lfieLeadData.getOthersInfo)
        commonPage.getButtonByName("Create")
        commonPage.getPopUpAssertion('Lead has been created')
        cy.url().then(data => {
            leadUrl = `/${data.substring(data.lastIndexOf('quotes/life/'))}`
            quoteId = `/${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log(leadUrl)
            cy.log(quoteId)
        })
    })

    it('Should Update Lead Status, Add/Delete Activity, Add/Delete Additional Contact', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(leadUrl)
        commonPage.verifyURL('/quotes/life')
        //Update Lead Status
        commonPage.getLeadStatus('New Lead')
        commonPage.getNotesField(lifeLeadData.lfieLeadData.othersInfo)
        commonPage.getButtonByName('Change Status')
        commonPage.getPopUpAssertion(' Lead Status has been Updated')

        //Add Activity
        cy.get('button#add-activity-btn').should('be.visible').click()
        commonPage.getModalAssertion()
        commonPage.getTitleField("Test Title")
        commonPage.getActivityDescriptionField(lifeLeadData.lfieLeadData.othersInfo)
        commonPage.getAsigneeDropdown("Advisor Saroosh - LIFE_ADVISOR")
        commonPage.getDueDateField()
        commonPage.selectDate('22')
        commonPage.getApplyButton()
        cy.get('button[type="submit"]').contains('Add Activity').should('be.visible').click()
        commonPage.getPopUpAssertion(" Activity has been Created")
        //Delete Activity
        commonPage.getDeleteActivityButton()
        cy.wait(4000)
        //Add Additional Contact
        commonPage.getAdditionalContactButton()
        commonPage.getContactType("Mobile Number")
        commonPage.getContactFeild('9874563201')
        commonPage.getAddContactButton('Add Contact')
        //Delete Additional Contact button
        // commonPage.getDeleteAdditionalContactButton()
    })

    it('Should Update Quote details', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(leadUrl)
        commonPage.verifyURL('/quotes/life')
        LifePage.getEditButton(quoteId)
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getDOB(quoteData.personalInfo.month, quoteData.personalInfo.year, quoteData.personalInfo.day)
        LifePage.getNationality("Pakistani")
        LifePage.getSumInsuredValue()
        LifePage.getPremiumField()
        LifePage.getCurrency(lifeLeadData.lfieLeadData.currency)
        LifePage.getPurposeInsuranceField(lifeLeadData.lfieLeadData.purpose)
        LifePage.getMartialStatus(lifeLeadData.lfieLeadData.martialStatus)
        LifePage.getChildren(lifeLeadData.lfieLeadData.children)
        LifePage.getTypeOfInsurance(lifeLeadData.lfieLeadData.InsuranceType)
        LifePage.getTenure(lifeLeadData.lfieLeadData.tenure)
        LifePage.getGender(lifeLeadData.lfieLeadData.gender)
        LifePage.getSmoker(lifeLeadData.lfieLeadData.smoker)
        commonPage.getButtonByName('Update')
    })





})