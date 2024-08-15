"""Imports for core project."""
from django.urls import path
from django.contrib import admin
from rest_framework import routers
from order import views as order_views
from source import views as source_views
from courier import views as courier_views
from rest_framework_simplejwt.views import (
    TokenObtainPairView,
    TokenRefreshView
)


admin.site.site_url = 'http://192.168.0.125/ecs/frontend/dist/'
admin.site.site_header = 'Elixir Customer Services'

router = routers.SimpleRouter()

router.register(r"action_customers", order_views.Action_Customer_ModelViewSet,
                basename="action_customer")

router.register(r"action_products", order_views.Action_Product_ModelViewSet,
                basename="action_product")

router.register(r"reasons", order_views.ReasonModelViewSet, basename="reason")

router.register(r"options", order_views.OptionModelViewSet, basename="option")

router.register(r"rooms", order_views.RoomModelViewSet, basename="room")

router.register(r"warehouses", order_views.WarehouseModelViewSet,
                basename="warehouse")

# Just aliases for frontend to call the warehouse
router.register(r"pickers", order_views.WarehouseModelViewSet,
                basename="warehouse")

router.register(r"packers", order_views.WarehouseModelViewSet,
                basename="warehouse")

router.register(r"couriers", courier_views.CourierModelViewSet,
                basename="courier")

router.register(r"orders", order_views.OrderModelViewSet,
                basename="order")

router.register(r"refunds", order_views.RefundModelViewSet, basename="refund")

router.register(r"resends", order_views.ResendModelViewSet, basename="resend")

router.register(r"pending_resends", order_views.PendingModelViewSet,
                basename="pending_resend")

router.register(r"returns", order_views.ReturnModelViewSet, basename="return")

router.register(r"return_items", order_views.ReturnItemsModelViewSet,
                basename="return_items")

router.register(r"pending_items", order_views.PendingItemsModelViewSet,
                basename="pending_items")

router.register(r"sources", source_views.SourceModelViewSet, basename="source")

router.register(r"claims", order_views.ClaimsModelViewSet, basename="claim")
router.register(r"claim_forms", order_views.ClaimFormsModelViewSet, basename="claims_form")
urlpatterns = [
    path("admin/", admin.site.urls),

    path("api/token/", TokenObtainPairView.as_view(),
         name="token_obtain_pair"),

    path("api/token/refresh/", TokenRefreshView.as_view(),
         name="token_refresh")
] + router.urls
