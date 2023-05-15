import CarLeadPage from '../../../pageObjects/CarLeadPage';
import CommonPage from '../../../pageObjects/CommonPage';
let carLeadData = require('../../../fixtures/carLeadData')
let qouteData = require('../../../fixtures/qoutesData')
describe('Car qoutes', () => {

  const commonPage = new CommonPage()
  const carLeadPage = new CarLeadPage()

  beforeEach(() => {
    cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'))
    cy.runRoutes()
  })

  it('Should create lead car qoutes insurance', () => {
    cy.visit('/quotes/car/create')
    commonPage.verifyURL('/quotes/car/create')

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
    commonPage.verifyURL('/quotes/car')


  })
})