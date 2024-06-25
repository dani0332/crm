import CommonPage from '../../../pageObjects/CommonPage';
import petPage from '../../../pageObjects/PetLeadPage';
let quoteData = require('../../../fixtures/qoutesData')
let petData = require('../../../fixtures/petLeadData.json')
let PetPage = require('../../../pageObjects/PetLeadPage')
let leadUrl
let quoteId
describe('Pet qoutes', () => {
    const commonPage = new CommonPage()
    const PetPage = new petPage()

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
    it('Should Create Pet Quote', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('/quotes/pet')
        commonPage.verifyURL('/quotes/pet')
        PetPage.getCreateLeadButton()
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        commonPage.getEmail(quoteData.personalInfo.email)
        commonPage.getMobileNumber(quoteData.personalInfo.phoneNumber)
        PetPage.getPremiumField()
        PetPage.getPolicyNumber()
        PetPage.getTypeOfPet("Cat")
        PetPage.getBreedOfPet("Russian")
        PetPage.getAgeOfPet('5')
        PetPage.getNeutred('Yes')
        PetPage.getMicroChipped('Yes')
        PetPage.getIsMixedBreed('No')
        PetPage.getInjury('No')
        PetPage.getPetGender('Male')
        PetPage.getAccomodationType("An apartment")
        PetPage.getPossessionType('A tenant')
        commonPage.getButtonByName('Create')
        cy.url().then(data => {
            leadUrl = `/${data.substring(data.lastIndexOf('quotes/pet/'))}`
            quoteId = `/${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log(leadUrl)
        })
        commonPage.getPopUpAssertion("Lead has been created")
    })

    it('Should Update Pet Quote', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        // cy.visit(leadUrl)
        cy.visit('quotes/pet/SEYAU9JW')
        commonPage.verifyURL('/quotes/pet')
        // PetPage.getEditButton(quoteId)
        PetPage.getEditButton('/SEYAU9JW')
        commonPage.getFirstNameField(quoteData.personalInfo.firstName)
        commonPage.getLastNameField(quoteData.personalInfo.lastName)
        PetPage.getPremiumField()
        PetPage.getPolicyNumber()
        PetPage.getTypeOfPet("Cat")
        PetPage.getBreedOfPet("Russian")
        PetPage.getAgeOfPet('5')
        PetPage.getNeutred('Yes')
        PetPage.getMicroChipped('Yes')
        PetPage.getIsMixedBreed('No')
        PetPage.getInjury('No')
        PetPage.getPetGender('Male')
        PetPage.getAccomodationType("An apartment")
        PetPage.getPossessionType('A tenant')
        commonPage.getButtonByName('Update')
    })

    //Skipped due to blocker (Unable to add activity)
    it.skip('Should Update Lead Status, Add/Delete Activity, Add/Delete Additional Contact,', () => {
        Cypress.on('uncaught:exception', err => {
            if (resizeObserverLoopErrRe.test(err.message)) {
                return false
            }
        })
        cy.visit('quotes/pet/SEYAU9JW')
        commonPage.verifyURL('/quotes/pet')
        //Update Lead
        commonPage.getLeadStatus('New Lead')
        commonPage.getNotesField(petData.petData.notes)
        commonPage.getButtonByName('Change Status')
        commonPage.getPopUpAssertion(' Lead Status has been Updated')

        //Add Activity
        PetPage.getAddActivityButton()
        commonPage.getModalAssertion()
        commonPage.getTitleField("Test Title")
        commonPage.getActivityDescriptionField(petData.petData.notes)
        commonPage.getDueDateField()
        commonPage.selectDate('25')
        commonPage.getApplyButton()
        commonPage.getButtonByName('Add Activity')
        // Will continue next whenever the blocker will be resolved.

    })

})
