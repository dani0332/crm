class PetLeadPage {

    getCreateLeadButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/quotes/pet/create"]').should('be.visible').click()
    }

    getPremiumField() {
        return cy.get('input#premium').should('be.visible').type('5')
    }

    getPolicyNumber() {
        return cy.get('input#policy_number').should('be.visible').clear().type('5')
    }

    getTypeOfPet(type) {
        return cy.get('input#type_of_pet1').should('be.visible').clear().type(type)
    }

    getBreedOfPet(breed) {
        return cy.get('input#breed_of_pet1').should('be.visible').clear().type(breed)
    }

    getAgeOfPet(age) {
        return cy.get('input#age_of_pet1').should('be.visible').clear().type(age)
    }

    getNeutred(neutered) {
        return cy.get('select#is_neutered').should('be.visible').select(neutered)
    }

    getMicroChipped(val) {
        return cy.get('select#is_microchipped').should('be.visible').select(val)
    }

    getIsMixedBreed(val) {
        return cy.get('select#is_mixed_breed').should('be.visible').select(val)
    }

    getInjury(val) {
        return cy.get('select#has_injury').should('be.visible').select(val)
    }

    getPetGender(gender) {
        return cy.get('select#gender').should('be.visible').select(gender)
    }

    getAccomodationType(type) {
        return cy.get('select#ilivein_accommodation_type_id').should('be.visible').select(type)
    }

    getPossessionType(possesion) {
        return cy.get('select#iam_possesion_type_id').should('be.visible').select(possesion)
    }

    //Update Quote Details
    getEditButton(uid) {
        return cy.get(`a[href="https://crmstage.alfred.ae/quotes/pet${uid}/edit"]`).should('be.visible').click()
    }

    //Add Activity
    getAddActivityButton() {
        return cy.get('button#add-activity-btn').should('be.visible').click()
    }


}
export default PetLeadPage;
