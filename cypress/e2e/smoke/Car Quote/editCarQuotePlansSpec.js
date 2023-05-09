import CarLeadPage from '../../../pageObjects/CarLeadPage';
import CommonPage from '../../../pageObjects/CommonPage';
let carLeadData = require('../../../fixtures/carLeadData')
let qouteData = require('../../../fixtures/qoutesData')

let CDBID
describe('Car qoutes', () => {

    const commonPage = new CommonPage()
    const carLeadPage = new CarLeadPage()
    before(() => {
        //Creating Quote using API
        cy.generate_CDBID(Cypress.env('Capi_X_Api_Token'), qouteData.carQouteData, carLeadData.carLeadData.endPoint).then((data) => {
            // cy.log(JSON.stringify(data))
            CDBID = data
            cy.log(CDBID)
        })
    })
    beforeEach(() => {
        cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'))
        cy.runRoutes()
    })
    it('Should edit car qoute details', () => {
        cy.visit(`/quotes/car/${CDBID}`)
        Cypress.on('uncaught:exception', (err, runnable) => {
            // returning false here prevents Cypress from
            // failing the test
            return false
        })

        //Edit Car Quote Plans
        carLeadPage.getviewPlansButton(CDBID)
        carLeadPage.getSwitchButton()
        carLeadPage.getInsurerQuoteNoField()
        carLeadPage.getActualPremiumField()
        carLeadPage.getCarValueField()
        carLeadPage.getExcessField()
        carLeadPage.getAncillaryExcess()

        //Addons Tab
        carLeadPage.getAddonsTab()
        carLeadPage.getAddonsByName('0', "10")
        carLeadPage.getAddonsByName('1', '10')
        carLeadPage.getAddonsByName('2', '20')
        carLeadPage.getAddonsByName('3', '30')
        carLeadPage.getAddonsByName('4', '40')
        carLeadPage.getAddonsByName('5', '50')

        //inclusions tab
        carLeadPage.getInclusionTab()

        //Exclusions tab
        carLeadPage.getExclusionsTab()

        //Road Side Assistance tab
        carLeadPage.getRsaTab()

        //Policy Tab
        carLeadPage.getPolicyDetailsTab()

        carLeadPage.getGeneralInfoTab()

        carLeadPage.getAddonSubmitButton()


    })
})