"""Admin Imports."""
from django.contrib import admin
from . import models


class OrderAdmin(admin.ModelAdmin):
    list_display = [f.name for f in models.Order._meta.fields]
    list_filter = ('source', 'courier',)
    search_fields = ('order_id', 'tracking_id', 'name',)


class OrderTypeAdmin(admin.ModelAdmin):
    list_filter = (
        'created', 'reason', 'option', 'order__source', 'order__courier', 'user',
    )
    ordering = (
        'created',
    )
    search_fields = (
        'order__order_id', 'order__tracking_id', 'order__name',
    )


class RefundAdmin(OrderTypeAdmin):
    list_display = [f.name for f in models.Refund._meta.fields]
    ordering = OrderTypeAdmin.ordering + (
        'order_total', 'full_refund', 'void_order', 'amount', 'dor',
    )


class ResendAdmin(OrderTypeAdmin):
    list_display = [f.name for f in models.Resend._meta.fields]
    list_filter = OrderTypeAdmin.list_filter + (
        'room', 'picker', 'packer',
    )
    ordering = OrderTypeAdmin.ordering + (
        'dor',
    )


class ReturnAdmin(OrderTypeAdmin):
    list_display = (
        'order', 'created', 'notes', 'reason', 'option',
        'action_customer', 'get_return_items', 'user',
    )
    list_filter = OrderTypeAdmin.list_filter + (
        'action_customer__name',
    )


class PendingAdmin(admin.ModelAdmin):
        list_display = (
            'order', 'original_order', 'courier', 'created',
            'reason', 'option', 'notes', 'room', 'picker',
            'packer', 'dor', 'get_pending_items', 'user',
        )
        list_filter = (
            'created', 'reason', 'option', 'original_order__source',
            'original_order__courier', 'room', 'picker', 'packer', 'user',
        )
        ordering = (
            'created', 'dor',
        )
        search_fields = (
            'original_order__order_id', 'original_order__tracking_id',
            'original_order__name',
        )


class NameBasedLookup(admin.ModelAdmin):
    list_display = ('name',)
    search_fields = ('name',)


class ItemAdmin(admin.ModelAdmin):
    list_display = (
        'order', 'sku', 'title', 'shipping', 'price',
        'qty',
    )
    ordering = (
        'shipping', 'price', 'qty'
    )
    search_fields = (
        'order__order__order_id', 'sku', 'title',
    )


class ReturnItemAdmin(ItemAdmin):
    list_display = ItemAdmin.list_display + (
        'action_qty', 'action_product',
    )
    ordering = ItemAdmin.list_display + (
        'action_qty',
    )
    list_filter = (
        'action_product',
    )


class WarehouseAdmin(admin.ModelAdmin):
    list_display = (
        'name', 'active',
    )
    list_filter = (
        'active',
    )
    search_fields = (
        'name',
    )


class ClaimFormsAdmin(admin.ModelAdmin):
    list_display = [f.name for f in models.ClaimForms._meta.fields]
    list_filter = (
        'courier', 'status', 'user'
    )
    ordering = (
        'claim_count', 'expected_payout', 'actual_payout',
        'created'
    )
    search_fields = (
        'reference', 'created'
    )


class ClaimsAdmin(admin.ModelAdmin):
    list_display = [f.name for f in models.Claims._meta.fields]
    list_filter = (
        'status', 'rejected'
    )
    search_fields = (
        'order',
    )


admin.site.register(models.Action_Customer, NameBasedLookup)
admin.site.register(models.Action_Product, NameBasedLookup)
admin.site.register(models.Reason, NameBasedLookup)
admin.site.register(models.Option, NameBasedLookup)
admin.site.register(models.Room, NameBasedLookup)
admin.site.register(models.Warehouse, WarehouseAdmin)
admin.site.register(models.Order, OrderAdmin)
admin.site.register(models.Resend, ResendAdmin)
admin.site.register(models.Pending, PendingAdmin)
admin.site.register(models.Return, ReturnAdmin)
admin.site.register(models.Refund, RefundAdmin)
admin.site.register(models.PendingItems, ItemAdmin)
admin.site.register(models.ReturnItems, ReturnItemAdmin)
admin.site.register(models.ClaimForms, ClaimFormsAdmin)
admin.site.register(models.Claims, ClaimsAdmin)
