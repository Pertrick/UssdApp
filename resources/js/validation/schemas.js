import * as yup from 'yup';

const phoneRegExp = /^([0-9\s\-\+\(\)]*)$/;
const passwordRegExp = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\w\W]{8,}$/;

export const businessRegistrationSchema = yup.object({
    // User fields
    name: yup.string()
        .required('Your name is required')
        .min(3, 'Your name must be at least 3 characters')
        .max(100, 'Your name must not exceed 100 characters'),
    email: yup.string()
        .required('Email is required')
        .email('Please enter a valid email address'),
    password: yup.string()
        .required('Password is required')
        .matches(
            passwordRegExp,
            'Password must contain at least 8 characters, one uppercase letter, one lowercase letter, and one number'
        ),
    password_confirmation: yup.string()
        .required('Please confirm your password')
        .oneOf([yup.ref('password')], 'Passwords must match'),
    
    // Business fields
    business_name: yup.string()
        .required('Business name is required')
        .min(3, 'Business name must be at least 3 characters')
        .max(100, 'Business name must not exceed 100 characters'),
    business_email: yup.string()
        .required('Business email is required')
        .email('Please enter a valid business email address'),
    phone: yup.string()
        .required('Phone number is required')
        .matches(phoneRegExp, 'Please enter a valid phone number')
        .min(10, 'Phone number must be at least 10 digits'),
    state: yup.string()
        .required('State is required')
        .min(2, 'State must be at least 2 characters'),
    city: yup.string()
        .required('City is required')
        .min(2, 'City must be at least 2 characters'),
    address: yup.string()
        .required('Address is required')
        .min(10, 'Address must be at least 10 characters'),
});

export const cacVerificationSchema = yup.object({
    cacNumber: yup.string()
        .required('CAC number is required')
        .matches(/^[A-Za-z0-9-]+$/, 'Please enter a valid CAC number'),
    businessType: yup.string()
        .required('Business type is required')
        .oneOf(['sole_proprietorship', 'partnership', 'limited_liability'], 'Please select a valid business type'),
    registrationDate: yup.date()
        .required('Registration date is required')
        .max(new Date(), 'Registration date cannot be in the future'),
    cacDocument: yup.mixed()
        .required('CAC document is required')
        .test('fileSize', 'File size must be less than 5MB', function(value) {
            if (!value) return this.createError({ message: 'CAC document is required' });
            return value.size <= 5000000;
        })
        .test('fileType', 'File must be PDF, JPG or PNG', function(value) {
            if (!value) return this.createError({ message: 'CAC document is required' });
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            return allowedTypes.includes(value.type);
        }),
});

export const directorInfoSchema = yup.object({
    directorName: yup.string()
        .required('Director name is required')
        .min(3, 'Director name must be at least 3 characters')
        .max(100, 'Director name must not exceed 100 characters'),
    directorPhone: yup.string()
        .required('Director phone is required')
        .matches(phoneRegExp, 'Please enter a valid phone number')
        .min(10, 'Phone number must be at least 10 digits'),
    directorEmail: yup.string()
        .required('Director email is required')
        .email('Please enter a valid email address'),
    idType: yup.string()
        .required('ID type is required')
        .oneOf(['national_id', 'drivers_license', 'international_passport'], 'Please select a valid ID type'),
    idNumber: yup.string()
        .required('ID number is required')
        .min(5, 'ID number must be at least 5 characters'),
    idDocument: yup.mixed()
        .required('ID document is required')
        .test('fileSize', 'File size must be less than 5MB', value => 
            !value || (value && value.size <= 5000000))
        .test('fileType', 'File must be PDF, JPG or PNG', value =>
            !value || (value && ['application/pdf', 'image/jpeg', 'image/png'].includes(value.type))),
});
