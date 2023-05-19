import CommonPage from '../../../pageObjects/CommonPage';
import HealthLead from '../../../pageObjects/GroupMedicalLeadPage';
let groupMedicalLead = require('../../../fixtures/groupMedicalLeadData')
let quoteData = require('../../../fixtures/qoutesData')
let leadUrl
let quoteId
describe('Group Mediacl Qoutes', () => {
    const commonPage = new CommonPage()
    const healthPage = new HealthLead()

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
    it('Should Create Group health Quote Lead', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/medical/amt')
        commonPage.verifyURL('/medical/amt')
        //Create Lead
        healthPage.getCreateLeadPageButton()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getEmail(quoteData.personalInfo.email)
        commonPage.getMobileNumber(quoteData.personalInfo.phoneNumber)
        healthPage.getCompanyNameField(groupMedicalLead.groupMedicalData.companyName)
        healthPage.getNumberOfEmployeesFeild(groupMedicalLead.groupMedicalData.noEmp)
        healthPage.getBusinessInsuranceType()
        healthPage.getBriefDetailsField(groupMedicalLead.groupMedicalData.dummyData)
        commonPage.getButtonByName('Create')
        commonPage.getPopUpAssertion('Lead has been stored')
        cy.url().then(data => {
            leadUrl = `/${data.substring(data.lastIndexOf('medical/amt'))}`
            quoteId = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log("Quote UID", quoteId)
            cy.log("Lead URL", leadUrl)
        })

        //Assign Lead
        healthPage.getAssignLeadDropdown('Adeel - GM_ADVISOR')
        healthPage.getAssignButton()
        commonPage.getPopUpAssertion('business Leads has been Assigned To Adeel')
    })

    it('Should Retrieve Quote Details', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/medical/amt')
        commonPage.verifyURL('/medical/amt')
        //Retrieve Lead
        healthPage.getCdbID(quoteId)
        healthPage.getSearchButton()
        healthPage.assertCDBID(quoteId)
        healthPage.getSearchButton()
        healthPage.getSearchResetButton()
        healthPage.getFirstNameField('Muhammad')
        healthPage.getSearchButton()
        healthPage.getSearchResetButton()
        healthPage.getLastNameField('Abdullah')
        healthPage.getSearchButton()
        healthPage.getSearchResetButton()
        healthPage.getEmailField()
        healthPage.getSearchButton()
        healthPage.getSearchResetButton()
        healthPage.getLeadDropdown('New Lead')
        healthPage.getSearchButton()
        healthPage.getSearchButton()
        healthPage.getSearchResetButton()



    })

    it('Should Edit Quote Details', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(`/medical/amt/${quoteId}`)
        // cy.visit('/medical/amt/NCMRDGJC')
        commonPage.verifyURL('/medical/amt')
        // cy.get('a[href="https://crmstage.alfred.ae/medical/amt/NCMRDGJC/edit"]').should('be.visible').click()
        healthPage.getEditButton(quoteId)
        commonPage.getFirstNameField(quoteData.personalInfo.firstName + " Updated")
        commonPage.getLastNameField(quoteData.personalInfo.lastName + " Updated")
        healthPage.getCompanyNameField(groupMedicalLead.groupMedicalData.companyName + " Updated")
        healthPage.getNumberOfEmployeesFeild("20")
        healthPage.getBusinessInsuranceType()
        healthPage.getGroupMedicalType('EBP Medium')
        healthPage.getPremiumField('10')
        commonPage.getButtonByName('Update')
        commonPage.getPopUpAssertion('Lead has been updated')

        //Lead Status
        commonPage.getPanelHeadingAssertion('Lead Status')
        healthPage.getLeadStatusDropdown('New Lead')
        healthPage.getNotesField('Test Notes')
        commonPage.getButtonByName('Change Status')
        commonPage.getPopUpAssertion(' Lead Status has been Updated')

        //Add Activity
        healthPage.gteAddActivityButton()
        healthPage.getAvtivityTitleField('Test Activity')
        healthPage.getDescriptionField(quoteData.personalInfo.dummyText)
        healthPage.getActivityAssignee('Muhammad Ali Riaz - AMT_ADVISOR')
        commonPage.getButtonByName('Add Activity')
        commonPage.getPopUpAssertion(' Activity has been Created')

        //edit activity
        healthPage.getActivityButtons('Edit')
        healthPage.getTitleField('Test Activity Updated')
        healthPage.getDescriptionField(quoteData.personalInfo.dummyText + ' Updated')
        healthPage.getEditAssignee('Danielle Andigan - AMT_ADVISOR')
        commonPage.getButtonByName('Update Activity')
        commonPage.getPopUpAssertion('Activity updated successfully')

        // Delete activity
        healthPage.getDeleteActivityButton()

        //Add Additional Contact
        healthPage.getAdditionalContactButton()
        healthPage.getAdditionalContactTypeButton('Mobile Number')
        healthPage.getValueField('950314560')
        commonPage.getButtonByName('Add Contact')

        //Delete Contact Button
        // healthPage.getDeleteContactButton()
    })



})