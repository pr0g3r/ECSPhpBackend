import { now, upperFirst } from 'lodash'

function orderSerializer(order) {
  return {
    order_id: order.order || order.order_id || order.orderID,
    source: upperFirst(order.source),
    courier: order.courier,
    tracking_id: order.tracking_id,
    name: order.name || order.buyer || null,
    contact: order.contact || order.email || null,
  }
}

function refundSerializer(order, total) {
  return {
    order: order.order,
    created: order.created ? order.created : now(),
    reason: order.reason,
    option: order.option,
    order_total: total,
    notes: order.notes ? order.notes : null,
    full_refund: order.full_refund,
    amount: order.full_refund ? total : order.amount,
    void_order: order.void_order ? true : false,
    dor: order.dor,
    user: localStorage.getItem('Ecs_User'),
  }
}

function resendSerializer(order) {
  let resend = {
    order: order.order,
    original_order: order.original_order,
    created: order.created ? order.created : now(),
    reason: order.reason,
    option: order.option,
    notes: order.notes ? order.notes : null,
    room: order.room,
    picker: order.picker || "Unknown",
    packer: order.packer || "Unknown",
    dor: order.dor,
    user: localStorage.getItem('Ecs_User') || null,
  }

  if (order.courier) resend.courier = order.courier

  return resend
}

function returnSerializer(order) {
  return {
    order: order.order || order.order_id,
    created: order.created ? order.created : now(),
    notes: order.notes ? order.notes : null,
    reason: order.reason,
    option: order.option,
    action_customer: order.action_customer,
    user: localStorage.getItem('Ecs_User'),
  }
}

function fespItemSerializer(item) {
  return {
    sku: item.SKU || item.sku,
    qty: item.quantity || item.qty,
    shipping: item.shipping || 0.0,
    price: item.price || 0.0,
    title: item.name || item.title,
    action_product: item.action_product || "Salvaged",
    action_qty: item.action_qty || item.quantity || item.qty,
  };
}

export { orderSerializer, refundSerializer, resendSerializer, returnSerializer, fespItemSerializer }
