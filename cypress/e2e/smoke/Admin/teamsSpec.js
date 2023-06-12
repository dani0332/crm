import CommonPage from '../../../pageObjects/CommonPage';
import TeamsPage from '../../../pageObjects/TeamsPage';
let id
describe('Admin Teams', () => {
    const commonPage = new CommonPage()
    const teamsPage = new TeamsPage()

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

    it('Should Create Team', () => {
        cy.visit('/generic/team')
        commonPage.verifyURL('/generic/team')
        commonPage.getPanelHeadingAssertion('Teams')
        //Create Role
        teamsPage.getCreateTeamButton()
        teamsPage.getNameField("Muhammad Abdullah")
        teamsPage.getProductDropdown('Team')
        teamsPage.getParentDropdown('Car')
        teamsPage.getIsActiveCheckbox()
        teamsPage.getIsActiveCheckbox()
        commonPage.getButtonByName('Create')
        commonPage.getPopUpAssertion('Team has been stored')
        teamsPage.getTeamsListButton()
        cy.url().then(data => {
            id = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log(id)
        })
    })

    it('Should Search and Edit Team', () => {
        cy.visit('/generic/team')
        commonPage.verifyURL('/generic/team')
        commonPage.getPanelHeadingAssertion('Teams')
        //Retrieve Teams
        teamsPage.getSearchField('Muhammad Abdullah')
        teamsPage.getOpenTeam()
        commonPage.getHeadingAssertion('Team Detail')
        //Edit Team Details
        teamsPage.getEditButton()
        teamsPage.getNameField("Muhammad Abdullah")
        teamsPage.getProductDropdown('Team')
        teamsPage.getParentDropdown('Car')
        teamsPage.getIsActiveCheckbox()
        teamsPage.getIsActiveCheckbox()
        teamsPage.getUpdateButton()
        commonPage.getPopUpAssertion('Team has been updated')
        teamsPage.getTeamListButton()
        //Pagination
        teamsPage.getPaginationButton('Next')

    })
})