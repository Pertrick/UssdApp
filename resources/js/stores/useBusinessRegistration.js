import { defineStore } from 'pinia';

export const useBusinessRegistration = defineStore('businessRegistration', {
    state: () => ({
        // Basic registration data
        businessData: {
            // User fields
            name: '',
            email: '',
            // Business fields
            business_name: '',
            business_email: '',
            phone: '',
            state: '',
            city: '',
            address: '',
        },
        // CAC verification data
        cacData: {
            cacNumber: '',
            businessType: '',
            registrationDate: '',
            cacDocument: null,
            tempCacDocumentPath: null,
        },
        // Director information
        directorData: {
            directorName: '',
            directorPhone: '',
            directorEmail: '',
            idType: '',
            idNumber: '',
            idDocument: null,
        },
        currentStep: 1,
        maxStepReached: 1,
        isEmailVerified: false,
    }),

    getters: {
        canProceedToStep: (state) => (step) => {
            console.log('Checking if can proceed to step:', step);
            console.log('Current state:', {
                currentStep: state.currentStep,
                maxStepReached: state.maxStepReached,
                isEmailVerified: state.isEmailVerified
            });

            switch (step) {
                case 1: // Registration
                    return true;
                case 2: // CAC Verification (now step 2)
                    return state.maxStepReached >= 2;
                case 3: // Director Information (now step 3)
                    return state.maxStepReached >= 3;
                case 4: // Email Verification (now step 4 - optional)
                    return state.maxStepReached >= 4;
                default:
                    return false;
            }
        },
    },

    actions: {
        initializeFromLocalStorage() {
            const savedState = localStorage.getItem('businessRegistration');
            if (savedState) {
                const parsed = JSON.parse(savedState);
                this.$patch(parsed);
                console.log('Loaded state from localStorage:', parsed);
            }
        },

        saveToLocalStorage() {
            const stateToSave = {
                businessData: this.businessData,
                cacData: {
                    ...this.cacData,
                    cacDocument: null, // Don't store File objects
                },
                directorData: {
                    ...this.directorData,
                    idDocument: null, // Don't store File objects
                },
                currentStep: this.currentStep,
                maxStepReached: this.maxStepReached,
                isEmailVerified: this.isEmailVerified,
            };
            localStorage.setItem('businessRegistration', JSON.stringify(stateToSave));
            console.log('Saved state to localStorage:', stateToSave);
        },

        setBusinessData(data) {
            console.log('Setting business data:', data);
            this.businessData = { ...this.businessData, ...data };
            this.maxStepReached = Math.max(this.maxStepReached, 2);
            this.saveToLocalStorage();
        },

        setEmailVerified(verified) {
            console.log('Setting email verified:', verified);
            this.isEmailVerified = verified;
            if (verified) {
                this.maxStepReached = Math.max(this.maxStepReached, 4);
            }
            this.saveToLocalStorage();
        },

        setCurrentStep(step) {
            console.log('Setting current step:', step);
            if (this.canProceedToStep(step)) {
                this.currentStep = step;
                this.maxStepReached = Math.max(this.maxStepReached, step);
                this.saveToLocalStorage();
            } else {
                console.warn('Cannot proceed to step:', step);
            }
        },

        setCacData(data) {
            console.log('Setting CAC data:', data);
            this.cacData = { ...this.cacData, ...data };
            this.maxStepReached = Math.max(this.maxStepReached, 3);
            this.saveToLocalStorage();
        },

        setCacDocument(document) {
            console.log('Setting CAC document:', document);
            this.cacData.cacDocument = document;
            this.saveToLocalStorage();
        },

        setDirectorData(data) {
            console.log('Setting director data:', data);
            this.directorData = { ...this.directorData, ...data };
            this.maxStepReached = Math.max(this.maxStepReached, 4);
            this.saveToLocalStorage();
        },

        setDirectorDocument(document) {
            console.log('Setting director document:', document);
            this.directorData.idDocument = document;
            this.saveToLocalStorage();
        },

        clearRegistrationData() {
            console.log('Clearing registration data');
            this.$reset();
            localStorage.removeItem('businessRegistration');
        },
    },
});
