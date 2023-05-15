class HomeLeadPage {

    getCreateButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/quotes/home/create"]').should('be.visible').click()
    }

    getPremiumField() {
        return cy.get('input#premium').should('be.visible').type('5')
    }

    getPolicyNumber() {
        return cy.get('input#policy_number').should('be.visible').type("Test")
    }

    getPossessionType(type) {
        return cy.get("select#iam_possesion_type_id").should('be.visible').select(type)
    }

    getAccomodationType(accomodation) {
        return cy.get('select#ilivein_accommodation_type_id').should('be.visible').select(accomodation)
    }

    getAddressFeild(address) {
        return cy.get('textarea#address').should('be.visible').type(address)
    }

    getCheckBox() {
        return cy.get('input#has_contents').should('be.visible').click()
    }

    getContentsField(value) {
        return cy.get('input#contents_aed').should('be.visible').type(value)
    }

    getPersonalBelongingsCheckBox() {
        return cy.get('input#has_personal_belongings').should('be.visible').click()
    }

    getPersonalBelongingField(value) {
        return cy.get('input#personal_belongings_aed').should('be.visible').type(value)
    }

    getHasBuilding() {
        return cy.get('input#has_building').should('be.visible').click()
    }

    getBuildingAEDField(value) {
        return cy.get('input#building_aed').should('be.visible').type(value)
    }



}
export default HomeLeadPage;
