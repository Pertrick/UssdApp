# Customer Registration Flow Configuration Guide

## 🎯 Correct Configuration for Customer Registration

The Customer Registration flow should be configured for **Input Collection**, not for displaying dynamic options from an API. Here's the correct configuration:

## ✅ **Flow Type: Dynamic Flow (Input Collection)**

### **Title/Header**
```
Register Customer
```

### **Dynamic Flow Configuration**

#### **Flow Type: Input Collection**
- **Purpose**: Collect user information step by step
- **Not**: Display dynamic options from API

#### **Input Steps Configuration**
```json
{
  "flow_type": "input_collection",
  "input_steps": [
    {
      "step_id": "collect_first_name",
      "prompt": "Enter your first name:",
      "input_type": "text",
      "validation": "required|min:2",
      "store_as": "first_name"
    },
    {
      "step_id": "collect_last_name",
      "prompt": "Enter your last name:",
      "input_type": "text", 
      "validation": "required|min:2",
      "store_as": "last_name"
    },
    {
      "step_id": "collect_email",
      "prompt": "Enter your email address:",
      "input_type": "email",
      "validation": "required|email",
      "store_as": "email"
    }
  ],
  "api_integration": {
    "api_config_id": "paystack_create_customer",
    "trigger_after": "all_inputs_collected",
    "success_flow": "payment_amount",
    "error_flow": "error"
  }
}
```

## 🔧 **Step-by-Step Configuration**

### **Step 1: Flow Type Selection**
- ✅ **Dynamic Flow** (for input collection)
- ❌ **Static Flow** (for simple menus)

### **Step 2: Dynamic Flow Configuration**
- **Data Source**: Input Collection (not API)
- **Flow Type**: Input Collection
- **Purpose**: Collect customer information

### **Step 3: Input Steps Setup**
1. **First Name Collection**
   - Prompt: "Enter your first name:"
   - Type: Text
   - Validation: Required, minimum 2 characters
   - Store as: `first_name`

2. **Last Name Collection**
   - Prompt: "Enter your last name:"
   - Type: Text
   - Validation: Required, minimum 2 characters
   - Store as: `last_name`

3. **Email Collection**
   - Prompt: "Enter your email address:"
   - Type: Email
   - Validation: Required, valid email format
   - Store as: `email`

### **Step 4: API Integration**
- **API**: Paystack Create Customer
- **Trigger**: After all inputs collected
- **Success Flow**: Payment Amount
- **Error Flow**: Error handling

## 📱 **User Experience Flow**

### **Step 1: User selects "New Customer"**
```
System: Register Customer
        Please provide your details:

        First Name:
```

### **Step 2: User enters first name**
```
User: John
System: Enter your last name:
```

### **Step 3: User enters last name**
```
User: Doe
System: Enter your email address:
```

### **Step 4: User enters email**
```
User: john.doe@example.com
System: Creating customer account...
        Customer created successfully!

        Proceeding to payment...
```

## ⚙️ **Technical Implementation**

### **Flow Steps Created**
1. `collect_first_name` - Input step for first name
2. `collect_last_name` - Input step for last name  
3. `collect_email` - Input step for email
4. `create_customer_api` - API call to Paystack
5. `navigate_to_payment` - Success message and navigation

### **Data Flow**
```
User Input → Validation → Storage → API Call → Success/Error
```

### **Session Data Structure**
```json
{
  "first_name": "John",
  "last_name": "Doe", 
  "email": "john.doe@example.com",
  "customer_data": {
    "customer_code": "CUS_123456",
    "customer_id": "12345"
  }
}
```

## 🚫 **Common Configuration Mistakes**

### **❌ Wrong: API Data Source**
- **Data Source API**: Paystack Create Customer
- **Options List Path**: data.bundles
- **Option Label Field**: name
- **Option Value Field**: id

**Why this is wrong**: Customer registration should collect input, not display options from an API.

### **✅ Correct: Input Collection**
- **Flow Type**: Input Collection
- **Input Steps**: 3 steps (name, last name, email)
- **API Integration**: After all inputs collected
- **Validation**: Required fields with proper validation

## 🎯 **Configuration Summary**

| Setting | Value | Purpose |
|---------|-------|---------|
| **Flow Type** | Dynamic Flow | Enable step-by-step processing |
| **Data Source** | Input Collection | Collect user information |
| **Input Steps** | 3 steps | First name, last name, email |
| **API Integration** | Paystack Create Customer | Create customer after input |
| **Validation** | Required fields | Ensure data quality |
| **Success Flow** | Payment Amount | Continue to payment |
| **Error Flow** | Error handling | Handle API failures |

## 🧪 **Testing the Configuration**

### **Test Flow**
1. Dial `*666#`
2. Select "1" (Make Payment)
3. Select "1" (New Customer)
4. Enter first name: "John"
5. Enter last name: "Doe"
6. Enter email: "john.doe@example.com"
7. Verify customer creation
8. Proceed to payment amount

### **Expected Results**
- ✅ Input prompts work correctly
- ✅ Validation works properly
- ✅ API call succeeds
- ✅ Success message displayed
- ✅ Navigation to payment flow

## 🔧 **Troubleshooting**

### **Issue: "Step not found" error**
- **Cause**: Flow steps not properly configured
- **Solution**: Ensure all input steps are created and active

### **Issue: API call fails**
- **Cause**: Paystack API configuration incorrect
- **Solution**: Check API keys and endpoint configuration

### **Issue: Validation errors**
- **Cause**: Input validation rules too strict
- **Solution**: Adjust validation rules in step configuration

## 📝 **Next Steps**

1. **Test the configuration** in the simulator
2. **Configure Paystack API keys** in the marketplace
3. **Test the complete flow** end-to-end
4. **Monitor API calls** and responses
5. **Handle error scenarios** appropriately

---

## 🎉 **Conclusion**

The Customer Registration flow is now properly configured for input collection with Paystack integration. Users will be guided through a step-by-step process to provide their information, which will then be used to create a customer account in Paystack before proceeding to payment.
