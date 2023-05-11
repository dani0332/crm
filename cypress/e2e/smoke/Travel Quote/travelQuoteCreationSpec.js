import CommonPage from '../../../pageObjects/CommonPage';
let quoteData = require('../../../fixtures/qoutesData')
let TravelData = require('../../../pageObjects/TravelData')
let travelLeadData = require('../../../fixtures/travelLeadData')
let leadUrl
describe('Health qoutes', () => {
    const commonPage = new CommonPage()
    const travelPage = new TravelData()

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
    it('Should create Travel lead and Update lead Status', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('quotes/travel')
        commonPage.verifyURL('/quotes/travel')
        cy.get('a[href="https://crmstage.alfred.ae/quotes/travel/create"]').should('be.visible').click()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getEmail(quoteData.personalInfo.email)
        commonPage.getMobileNumber(quoteData.personalInfo.phoneNumber)
        commonPage.getDOB(quoteData.personalInfo.month, quoteData.personalInfo.year, quoteData.personalInfo.day)
        travelPage.getPremiumField(travelLeadData.travelLeadData.premium)
        travelPage.getDaysCoverForField(travelLeadData.travelLeadData.daysForCover)
        commonPage.getNationalityId(travelLeadData.travelLeadData.nationality)
        travelPage.getDestinationId(travelLeadData.travelLeadData.destination)
        travelPage.getRegionsCoverFor(travelLeadData.travelLeadData.regionCoverFor)
        travelPage.getTravelCoverFor(travelLeadData.travelLeadData.travelCoverFor)
        travelPage.getDescriptionField(travelLeadData.travelLeadData.details)
        travelPage.getCurrentlyLocated(travelLeadData.travelLeadData.currentlyLocated)
        travelPage.getCreateButton("Create")
        travelPage.getPanelAssertion('Travel Detail')
        cy.url().then(data => {
            leadUrl = `/${data.substring(data.lastIndexOf('quotes/travel/'))}`
            cy.log(leadUrl)
        })
        travelPage.getPopUpAssertion("Lead has been created")
        commonPage.verifyURL('/quotes/travel')

        //Update Lead Status
        travelPage.getPanelAssertion('Lead Status')
        travelPage.getleadStatusDropdown(travelLeadData.travelLeadData.leadStatus)
        travelPage.getNotesField(travelLeadData.travelLeadData.leadStatusNotes)
        travelPage.getCreateButton('Change Status')
        travelPage.getPopUpAssertion(" Lead Status has been Updated")
    })

    it('Should Add Member,Add Activity, Delete Activity, Add Additional Contact, Delete Additional Contact and Fetch&View available plans', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit(leadUrl)
        commonPage.verifyURL('/quotes/travel')
        //Add Member
        travelPage.getAddMemberButton()
        travelPage.getAddMemberPopUpAssertion()
        travelPage.getCreateButton('Save')
        travelPage.getDeleteButton()
        commonPage.getApiIntercept('@deleteTraveler', 302)
        cy.wait(4000)

        //Available plan
        travelPage.getPanelAssertion('Available Plans')
        travelPage.getCoopyLinkButton()
        travelPage.getCopiedAssertion()
        travelPage.getActivityButton()
        travelPage.getModalAssertion()
        travelPage.getTitleField("Test title")
        travelPage.getActivityDescriptionField(travelLeadData.travelLeadData.details)
        travelPage.getAsigneeDropdown('Sanya Garg - TRAVEL_ADVISOR')
        travelPage.getDueDateField()
        travelPage.selectDate("22")
        travelPage.getApplyButton()
        travelPage.getCreateButton('Add Activity')
        travelPage.getPopUpAssertion("Activity has been Created")

        //Delete Activity
        travelPage.getDeleteActivityButton()
        commonPage.getApiIntercept('@deleteActivity', 302)
        cy.wait(3000)

        //Add Additional Contact
        travelPage.getAdditionalContactButton()
        travelPage.getAdditionalContactType("Email")
        travelPage.getValueField("muhammad.abdull@insurancemarket.ae")
        travelPage.getCreateButton('Add Contact')

        //Delete Additional Contact
        travelPage.getDeleteAdditionalContactButton()

    })

    it('Should edit quote Details', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        // cy.visit(leadUrl)
        cy.visit('/quotes/travel/hflwtv3y')
        commonPage.verifyURL('/quotes/travel')
        travelPage.getEditButton()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getDOB(quoteData.personalInfo.month, quoteData.personalInfo.year, quoteData.personalInfo.day)
        travelPage.getPremiumField(travelLeadData.travelLeadData.premium)
        travelPage.getDaysCoverForField(travelLeadData.travelLeadData.daysForCover)
        commonPage.getNationalityId(travelLeadData.travelLeadData.nationality)
        travelPage.getDestinationId(travelLeadData.travelLeadData.destination)
        travelPage.getRegionsCoverFor(travelLeadData.travelLeadData.regionCoverFor)
        travelPage.getTravelCoverFor(travelLeadData.travelLeadData.travelCoverFor)
        travelPage.getDescriptionField(travelLeadData.travelLeadData.details)
        travelPage.getCurrentlyLocated(travelLeadData.travelLeadData.currentlyLocated)
        travelPage.getCreateButton("Update")
        travelPage.getPopUpAssertion("Travel has been updated")
    })


})