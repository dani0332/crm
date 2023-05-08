import CarLeadPage from '../../../pageObjects/CarLeadPage';
import CommonPage from '../../../pageObjects/CommonPage';
let carLeadData=require('../../../fixtures/carLeadData')
let qouteData=require('../../../fixtures/qoutesData')

let CDBID
describe('Car qoutes', () => {

  const commonPage=new CommonPage()
  const carLeadPage = new CarLeadPage()
  before(()=>{
    //Creating Quote using API
    cy.generate_CDBID(Cypress.env('Capi_X_Api_Token'),qouteData.carQouteData).then((data)=>{
      // cy.log(JSON.stringify(data))
      CDBID=data
      cy.log(CDBID)
    })
  })
  beforeEach(()=>{
    cy.loginByCookies(Cypress.env('imcrm_session'),Cypress.env('XSRF-TOKEN'))
    cy.runRoutes()
  })
  it('Should edit car qoute details', () => {
      cy.visit(`/quotes/car/${CDBID}`)
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

    //Change Lead Status
    carLeadPage.setLeadStatus()
    carLeadPage.getChangeStatusButton()

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
    carLeadPage.getUploadDocButton(CDBID)
    carLeadPage.getUploadFile()
    carLeadPage.getBackButton(CDBID)

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

    // Add Additional Contact commented for now because it is not responding
    carLeadPage.getAdditionalContactButton()
    carLeadPage.getContactType()
    carLeadPage.getContactValue()
    carLeadPage.getAddContactSubmitButton()
    carLeadPage.deleteContactButton()

  })
})