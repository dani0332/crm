import CommonPage from '../../../pageObjects/CommonPage';
import homePage from '../../../pageObjects/HomeLeadPage';
let quoteData = require('../../../fixtures/qoutesData')
let homeData = require('../../../fixtures/homeLeadData.json')
let HomePage = require('../../../pageObjects/HomeLeadPage')
let leadUrl
let quoteId
describe('Home qoutes', () => {
    const commonPage = new CommonPage()
    const HomePage = new homePage()

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
    it('Should Create Home Quote', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/quotes/home')
        commonPage.verifyURL('/quotes/home')
        HomePage.getCreateButton()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getEmail(quoteData.personalInfo.email)
        commonPage.getMobileNumber(quoteData.personalInfo.phoneNumber)
        HomePage.getPremiumField()
        HomePage.getPolicyNumber()
        HomePage.getPossessionType('A tenant')
        HomePage.getAccomodationType('An apartment')
        HomePage.getAddressFeild(homeData.homeData.dummyAddress)
        HomePage.getCheckBox()
        HomePage.getContentsField('1000')
        HomePage.getPersonalBelongingsCheckBox()
        HomePage.getPersonalBelongingField('1000')
        commonPage.getButtonByName('Create')
        cy.url().then(data => {
            leadUrl = `/${data.substring(data.lastIndexOf('quotes/home/'))}`
            cy.log(leadUrl)
        })
        commonPage.getPopUpAssertion("Lead has been created")
    })

    it('Should Update Lead Status, Add Activity, Add Additional Contact', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(leadUrl)
        // cy.visit('quotes/home/AK66ATVC')

        //Lead Status
        commonPage.getHeadingAssertion('Lead Status')
        commonPage.getLeadStatus('New Lead')
        commonPage.getNotesField(quoteData.personalInfo.dummyText)
        commonPage.getButtonByName('Change Status')
        commonPage.getPopUpAssertion('Lead Status has been Updated')


        //Add Activity
        commonPage.getHeadingAssertion('Lead Activities')
        cy.get('button#add-activity-btn').should('be.visible').click()
        commonPage.getModalAssertion()
        commonPage.getTitleField(homeData.homeData.title)
        commonPage.getActivityDescriptionField(quoteData.personalInfo.dummyText)
        commonPage.getAsigneeDropdown('Afzal Khan - HOME_ADVISOR')
        commonPage.getDueDateField()
        commonPage.selectDate('26')
        commonPage.getApplyButton()
        commonPage.getButtonByName('Add Activity')
        commonPage.getPopUpAssertion(' Activity has been Created')

        //Delete Activity
        commonPage.getDeleteActivityButton()
        cy.wait(3000)

        //Add Additional Contact
        commonPage.getAdditionalContactButton()
        commonPage.getContactType("Email")
        commonPage.getContactFeild("automation@gmail.com")
        commonPage.getAddContactButton()

        //Delete Contact
        // commonPage.getDeleteAdditionalContactButton()
    })

})