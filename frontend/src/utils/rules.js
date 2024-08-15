/**
 * Set of validation rules for various form fields.
 */
export default {
  order: {
    required: true,
    message: 'Please enter an order number',
    trigger: 'blur',
  },
  created: {
    type: 'date',
    required: true,
    message: 'Please enter create date for this record',
    trigger: 'blur',
  },
  courier: {
    required: true,
    message: 'Please select courier',
    trigger: 'blur',
  },
  reason: {
    required: true,
    message: 'Please select reason type for record',
    trigger: 'blur',
  },
  room: {
    required: true,
    message: 'Please select order room',
    trigger: 'blur',
  },
  picker: {
    required: false,
  },
  packer: {
    required: false,
  },
  sku: {
    required: true,
    message: 'Please select a sku',
    trigger: 'blur',
  },
  ghostSku: {
    required: true,
    message: 'Please select a sku',
    trigger: 'blur',
  },
  title: {
    required: true,
    message: 'Please enter a title',
    trigger: 'blur',
  },
  qty: {
    type: 'integer',
    required: true,
    message: 'Please enter a valid qty',
    trigger: 'blur',
  },
  price: {
    type: 'number',
    required: true,
    message: 'Please enter a valid price',
    trigger: 'blur',
  },
  shipping: {
    type: 'number',
    required: true,
    message: 'Please enter a valid shipping price',
    trigger: 'blur',
  },
  action_product: {
    required: true,
    message: 'Please select an action',
    trigger: 'blur',
  },
  action_qty: {
    type: 'integer',
    required: true,
    message: 'Please enter action qty',
    trigger: 'blur',
  },
  option: {
    required: true,
    message: 'Please select an option',
    trigger: 'blur',
  },
  name: {
    required: true,
    message: 'Please enter a name',
    trigger: 'blur',
  },
  date: {
    type: 'date', 
    required: true,
    message: 'Please enter order date',
    trigger: 'blur',
  },
  action_customer: {
    required: true,
    message: 'Please select a customer action',
    trigger: 'blur',
  },
  amount: {
    required: true,
    message: 'Please enter refund amount',
    trigger: 'blur',
  },
  buyer: {
    required: true,
    message: 'Please enter buyer name',
    trigger: 'blur',
  },
  phone: {
    required: true,
    pattern: '[0-9]{11}',
    message: 'Please enter valid phone number',
    trigger: 'blur',
  },
  email: {
    type: 'email',
    required: true,
    message: 'Please enter valid email format',
    trigger: 'blur',
  },
  address1: {
    required: true,
    message: 'Please enter address line 1',
    trigger: 'blur',
  },
  city: {
    required: true,
    message: 'Please city for address line 1',
    trigger: 'blur',
  },
  county: {
    required: true,
    message: 'Please enter a county',
    trigger: 'blur',
  },
  country: {
    required: true,
    message: 'Please enter a country',
    trigger: 'blur',
  },
  postcode: {
    required: true,
    message: 'Please enter a postcode',
    trigger: 'blur',
  },
  channel: {
    required: true,
    message: 'Please select an order channel',
    trigger: 'blur',
  },
  noOfLabels: {
    type: 'number',
    required: true,
    message: 'Please enter of parcels for order',
    trigger: 'blur',
  },
  weight: {
    type: 'number',
    required: true,
    message: 'Please enter weight of order',
    trigger: 'blur',
  },
  length: {
    type: 'number',
    required: true,
    message: 'Please enter length of order',
    trigger: 'blur',
  },
  username: {
    required: true,
    message: 'Please enter a username',
    trigger: 'blur',
  },
  password: {
    required: true,
    message: 'Please enter a password',
    trigger: 'blur',
  },
}
