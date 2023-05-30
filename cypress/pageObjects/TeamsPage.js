class TeamsPage {

    //Create Team
    getCreateTeamButton() {
        return cy.get('a[href*="generic/team/create"]').should('be.visible').contains('Create Team').click()
    }

    getNameField(name) {
        return cy.get('input#name').should('be.visible').clear().type(name)
    }

    getProductDropdown(val) {
        return cy.get('select#type').should('be.visible').select(val)
    }

    getParentDropdown(val) {
        return cy.get('select#parent_team_id').should('be.visible').select(val)
    }

    getIsActiveCheckbox() {
        return cy.get('input[type="checkbox"]').should('be.visible').click()
    }

    getTeamsListButton() {
        return cy.get('a.btn-sm').contains('Teams List').should('be.visible').click()
    }

    //Search & Edit Team
    getSearchField(data) {
        return cy.get('input#name').should('be.visible').type(data).type('{enter}')
    }

    getOpenTeam() {
        return cy.get('[role="row"] a[href*="/generic/team/"]').should('be.visible').eq(0).click()
    }

    //Edit Team Details
    getEditButton() {
        return cy.get('a.btn-sm').should('be.visible').contains('Edit').click()
    }

    getUpdateButton() {
        return cy.get('button[type="submit"]').should('be.visible').contains('Update').click()
    }

    getTeamListButton() {
        return cy.get('a[href*="generic/team"]').should('be.visible').contains('Team List').click()
    }

    //Bottom Slider (Pagination)
    getPaginationButton(name) {
        return cy.get('a[href="#"]').should('be.visible').contains(name).click()
    }



}
export default TeamsPage