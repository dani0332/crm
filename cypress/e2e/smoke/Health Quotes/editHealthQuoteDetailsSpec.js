import CommonPage from '../../../pageObjects/CommonPage';
import HealthLeadPage from '../../../pageObjects/HealthLeadPage';
let quoteData = require('../../../fixtures/qoutesData.json')
let healthLeadData = require('../../../fixtures/healthLeadData.json')
let CDBID
describe('Health qoutes', () => {

    const commonPage = new CommonPage()
    const healthLeadPage = new HealthLeadPage()
    // before(() => {
    //     //Creating Quote using API
    //     cy.generate_CDBID(Cypress.env('Capi_X_Api_Token'), quoteData.healthQuoteData, healthLeadData.healthData.endPoint).then((data) => {
    //         // cy.log(JSON.stringify(data))
    //         CDBID = data
    //         cy.log(CDBID)
    //     })
    // })
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

    it('Should perform search Health Quote operations', () => {
        cy.visit('/quotes/health')
        commonPage.verifyURL('/quotes/health')
        healthLeadPage.getTextFieldByName('CDB ID', healthLeadData.healthData.healthCDBID)
        healthLeadPage.getSearchButton()
        healthLeadPage.getOpenQuoteButton()
        healthLeadPage.getEditButton()
        healthLeadPage.getHealthTextFieldByName("FIRST NAME", "Muhammad Updated")
        healthLeadPage.getHealthTextFieldByName("LAST NAME", "Abdullah Updated")
        healthLeadPage.getHealthTextFieldByName("PREMIUM", healthLeadData.healthData.premium)
        healthLeadPage.getHealthTextFieldByName("DETAILS", "Test Details")
        healthLeadPage.getNationalityDropdown("NATIONALITY")
        healthLeadPage.getNationalityValue("Pakistani")
        healthLeadPage.getHealthDetailDropdown("EMIRATE OF YOUR VISA")
        healthLeadPage.getEmirateOfVisaValue()
        healthLeadPage.getMemberCategoryDropdown("MEMBER CATEGORY")
        healthLeadPage.getMemberCategoryValue()
        healthLeadPage.getSubmitButton()
    })

    it('Should Add member', () => {
        cy.visit(`/quotes/health/`)
        commonPage.verifyURL(`/quotes/health/`)
        healthLeadPage.getTextFieldByName('CDB ID', healthLeadData.healthData.healthCDBID)
        healthLeadPage.getSearchButton()
        healthLeadPage.getOpenQuoteButton()
        //Add Member
        healthLeadPage.getAddMemberButton()
        healthLeadPage.getAddMemberNationalityDropdown("Nationality")
        healthLeadPage.getNationalityValue("Pakistani")
        healthLeadPage.getAddMemberDropdown("Emirate of Visa")
        healthLeadPage.getAddMemberDropdownVisa("Dubai")
        healthLeadPage.getAddMemberDropdown("Gender")
        healthLeadPage.getAddMemberDropdownVisa("Male")
        healthLeadPage.getAddMemberDOB("DOB")
        healthLeadPage.getDate()
        healthLeadPage.getMemberCategoryDropdown("Member Category")
        healthLeadPage.getAddMemberDropdowns('Employee')
        healthLeadPage.getMemberCategoryDropdown("Salary Band")
        healthLeadPage.getAddMemberDropdowns('More than AED 4000')
        healthLeadPage.getSubmitButton()
    })

    it('Should Add Lead Status', () => {
        cy.visit(`/quotes/health/`)
        commonPage.verifyURL(`/quotes/health/`)
        healthLeadPage.getTextFieldByName('CDB ID', healthLeadData.healthData.healthCDBID)
        healthLeadPage.getSearchButton()
        healthLeadPage.getOpenQuoteButton()
        healthLeadPage.getAddAdditionalContactButton()
        healthLeadPage.getLeadStatusDropdown("Type")
        healthLeadPage.selectMobileNumber()
        healthLeadPage.getAddAdditionalContactvalueField()
        healthLeadPage.getAdditionalContactSubmitButton()
        healthLeadPage.getButtonByName(" Load History Data ")
        healthLeadPage.getButtonByName(" Load Audit Logs ")

    })
})