import CarLeadPage from '../../../pageObjects/CarLeadPage';
import CommonPage from '../../../pageObjects/CommonPage';
let carLeadData = require('../../../fixtures/carLeadData')
let qouteData = require('../../../fixtures/qoutesData')
let leadUrl
let quoteId
describe('Car qoutes', () => {

  const commonPage = new CommonPage()
  const carLeadPage = new CarLeadPage()
  Cypress.on('uncaught:exception', (err, runnable) => {
    // returning false here prevents Cypress from
    // failing the test
    return false
  })

  beforeEach(() => {
    cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'))
    cy.runRoutes()
  })

  it('Should create lead car qoutes insurance', () => {
    cy.visit('/quotes/car')
    commonPage.verifyURL('/quotes/car')
    carLeadPage.getCreateLeadButton()
    commonPage.getFirstNameField(qouteData.personalInfo.firstName)
    commonPage.getLastNameField(qouteData.personalInfo.lastName)
    commonPage.getDOB(qouteData.personalInfo.month, qouteData.personalInfo.year, qouteData.personalInfo.day)
    commonPage.getMobileNumber(qouteData.personalInfo.phoneNumber)
    commonPage.getEmail(qouteData.personalInfo.email)
    commonPage.getNationalityId(carLeadData.carLeadData.nationality)
    carLeadPage.getUaeLicenseHeldFor(carLeadData.carLeadData.licenseHeldFor)
    carLeadPage.getBackHomeLicense(carLeadData.carLeadData.backHome)
    carLeadPage.getCarMakeId(carLeadData.carLeadData.carMakeId)
    carLeadPage.getCarModelId(carLeadData.carLeadData.carModelId)
    carLeadPage.getManufacturYear(carLeadData.carLeadData.ManufactureYear)
    carLeadPage.getCarValue(carLeadData.carLeadData.carValue)
    carLeadPage.getVehicleType(carLeadData.carLeadData.vehicleType)
    carLeadPage.getSeatCapacity(carLeadData.carLeadData.seatCapacity)
    carLeadPage.getRegistrationId(carLeadData.carLeadData.registrationId)
    carLeadPage.getInsuranceType(carLeadData.carLeadData.typeOfInsurance)
    carLeadPage.getCurrentInsurance(carLeadData.carLeadData.currentInsurance)
    carLeadPage.getClaimHistory(carLeadData.carLeadData.claimHistory)
    carLeadPage.getCreateButton().click()
    cy.wait(4000)
    cy.url().then(data => {
      leadUrl = `/${data.substring(data.lastIndexOf('quotes/car/'))}`
      quoteId = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
      cy.log(leadUrl)
    })
    commonPage.verifyURL('/quotes/car')
  })

  it('Should edit car qoute details', () => {
    cy.visit(`${leadUrl}`)
    Cypress.on('uncaught:exception', (err, runnable) => {
      // returning false here prevents Cypress from
      // failing the test
      return false
    })

    commonPage.getEditButton()
    commonPage.getFirstNameField("Muhammad Updated")
    commonPage.getLastNameField("Abdullah Updated")
    commonPage.getDOB("Feb", "1999", "15")
    commonPage.getNationalityId("Pakistani")
    carLeadPage.getUaeLicenseHeldFor("10")
    carLeadPage.getBackHomeLicense("3")
    carLeadPage.getCarMakeId("AUDI")
    carLeadPage.getCarModelId("A4")
    commonPage.getSuccessAssertion()
    carLeadPage.getManufacturYear("2022")
    carLeadPage.getVehicleType("SEDAN")
    carLeadPage.getSeatCapacity("4")
    carLeadPage.getRegistrationId("Ajman")
    carLeadPage.getInsuranceType("Third Party Only")
    carLeadPage.getCurrentInsurance("Adamjee Insurance")
    carLeadPage.getClaimHistory("No claims for 4 years")
    carLeadPage.getUpdateButton()
  })

  it('Should Update Status, Edit Assumptions, Upload Documents, Add/Update/Delete Activity & Add Additional Contact', () => {
    cy.visit(`/quotes/car/${quoteId}`)
    Cypress.on('uncaught:exception', (err, runnable) => {
      // returning false here prevents Cypress from
      // failing the test
      return false
    })
    //Change Lead Status
    carLeadPage.setLeadStatus()
    carLeadPage.getChangeStatusButton()
    commonPage.getPopUpAssertion(' Lead Status has been Updated')

    //Edit assumptions
    carLeadPage.getEditAssumptionsButtons()
    carLeadPage.getSeatCapacity("7")
    carLeadPage.getVehicleType("LUXURY")
    carLeadPage.getVehivleModified()
    carLeadPage.getBankFinanced()
    carLeadPage.getGCCStandard()
    carLeadPage.getRegistrationYear()
    carLeadPage.getAssumptionsUpdateButton()

    //Upload Documents
    carLeadPage.getUploadDocButton(quoteId)
    carLeadPage.getUploadFile()
    carLeadPage.getBackButton(quoteId)

    // Send notes to customer
    carLeadPage.getSendNotesToCustomerButton()
    carLeadPage.getNotesDescription()
    carLeadPage.getSendNotesToCustomerSubmitButton()


    //Add Activity
    carLeadPage.getAddActivityButton()
    carLeadPage.getTitleField()
    carLeadPage.getDescriptionField(carLeadData.carLeadData.dummyData)
    carLeadPage.getSelectAssigneeDropDown()
    carLeadPage.getModalSubmitButton()

    //Update Activity
    carLeadPage.editActivityButton()
    carLeadPage.editDescription()
    carLeadPage.getModalUpdateButton()

    // Delete Activity
    carLeadPage.deleteActivityButton()

    // Add Additional Contact 
    carLeadPage.getAdditionalContactButton()
    carLeadPage.getContactType()
    carLeadPage.getContactValue()
    carLeadPage.getAddContactSubmitButton()
  })
})