"""Import for Order Serializers."""
from .models import (
    Action_Customer,
    Action_Product,
    Reason,
    Option,
    Room,
    Warehouse,
    Order,
    Refund,
    Resend,
    Pending,
    Return,
    ReturnItems,
    PendingItems,
    Claims,
    ClaimForms,
)
from source.models import Source
from courier.models import Courier
from rest_framework import serializers
from django.contrib.auth.models import User


class Action_Customer_Serializer(serializers.ModelSerializer):
    """Define response properties for all Action_Customer requests."""

    class Meta:
        """Define return fields for all Action_Customer requests."""

        model = Action_Customer
        fields = ["name"]


class Action_Product_Serializer(serializers.ModelSerializer):
    """Define response properties for all Action_Product requests."""

    class Meta:
        """Define return fields for all Action_Products requests."""

        model = Action_Product
        fields = ["name"]


class ClaimFormsSerializer(serializers.ModelSerializer):
    """Define response properties for all write based Claim Form requests."""

    courier = serializers.SlugRelatedField(
        slug_field="name", queryset=Courier.objects.all()
    )

    class Meta:
        """Define return fields for all Claim Form requests."""

        model = ClaimForms
        fields = "__all__"


class ReadClaimFormsSerializer(serializers.ModelSerializer):
    """Define response properties for all read based Claim Form requests."""

    user = serializers.SlugRelatedField(
        slug_field="username", queryset=User.objects.all()
    )

    courier = serializers.SlugRelatedField(
        slug_field="name", queryset=Courier.objects.all()
    )

    class Meta:
        """Define return fields for all Claim Form requests."""

        model = ClaimForms
        fields = "__all__"

class ReasonSerializer(serializers.ModelSerializer):
    """Define response properties for all Reason requests."""

    class Meta:
        """Define return fields for all Reason requests."""

        model = Reason
        fields = ["name"]


class OptionSerializer(serializers.ModelSerializer):
    """Define response properties for all Option requests."""

    class Meta:
        """Define return fields for all Option requests."""

        model = Option
        fields = ["name"]


class RoomSerializer(serializers.ModelSerializer):
    """Define response properties for all Room requests."""

    class Meta:
        """Define return fields for all Room requests."""

        model = Room
        fields = ["name"]


class WarehouseSerializer(serializers.ModelSerializer):
    """Define response properties for all Warehouse requests."""

    class Meta:
        """Define return fields for all Warehouse requests."""

        model = Warehouse
        fields = ["name"]


class OrderSerializer(serializers.ModelSerializer):
    """Define response properties for all Order requests."""

    source = serializers.SlugRelatedField(
        slug_field="name", queryset=Source.objects.all()
    )

    courier = serializers.SlugRelatedField(
        slug_field="name", queryset=Courier.objects.all()
    )

    class Meta:
        """Define return fields for all Order requests."""

        model = Order
        fields = "__all__"


class OrderTypeSerializer(serializers.ModelSerializer):
    """Define response properties for all Order Type requests."""

    order = serializers.SlugRelatedField(
        slug_field="order_id", queryset=Order.objects.all()
    )

    reason = serializers.SlugRelatedField(
        slug_field="name", queryset=Reason.objects.all()
    )

    option = serializers.SlugRelatedField(
        slug_field="name", queryset=Option.objects.all()
    )

    class Meta:
        """Define return fields for all Order Type requests."""

        fields = "__all__"


class RefundSerializer(OrderTypeSerializer):
    """
    Define response properties for all write based Refund requests.

    Inherit properties defined in OrderTypeSerializer.
    """

    class Meta:
        """
        Define return fields for all Refund requests.

        Inherits extra_fields from from OrderTypeSerializer.
        """

        model = Refund
        fields = "__all__"
        extra_fields = OrderTypeSerializer.Meta.fields


class ResendSerializer(OrderTypeSerializer):
    """
    Define response properties for all write based Resend requests.

    Inherit properties defined in OrderTypeSerializer.
    """

    room = serializers.SlugRelatedField(slug_field="name", queryset=Room.objects.all())

    picker = serializers.SlugRelatedField(
        slug_field="name", queryset=Warehouse.objects.all(), required=False,
    )

    packer = serializers.SlugRelatedField(
        slug_field="name", queryset=Warehouse.objects.all(), required=False,
    )

    class Meta:
        """
        Define return fields for all Resend requests.

        Inherits extra_fields from from OrderTypeSerializer.
        """

        model = Resend
        fields = "__all__"
        extra_fields = OrderTypeSerializer.Meta.fields


class PendingSerializer(ResendSerializer):
    """
    Define response properties for all write based Pending requests.

    Inherit properties defined in ResendSerializer.
    """

    order = serializers.CharField(required=False, allow_blank=True)

    courier = serializers.SlugRelatedField(
        slug_field="name", queryset=Courier.objects.all()
    )

    class Meta:
        """
        Define return fields for all Pending requests.

        Inherits extra_fields from ResendSerializer.
        """

        model = Pending
        fields = "__all__"
        extra_fields = ResendSerializer.Meta.fields


class ReturnSerializer(OrderTypeSerializer):
    """
    Define response properties for all Return requests.

    Inherit properties defined in OrderTypeSerializer.
    """

    action_customer = serializers.SlugRelatedField(
        slug_field="name", queryset=Action_Customer.objects.all()
    )

    class Meta:
        """
        Define return fields for all Return requests.

        Inherits extra_fields from from OrderTypeSerializer.
        """

        model = Return
        fields = "__all__"
        extra_fields = OrderTypeSerializer.Meta.fields


class ReturnItemSerializer(serializers.ModelSerializer):
    """Define response properties for ReturnItems requests."""

    action_product = serializers.SlugRelatedField(
        slug_field="name", queryset=Action_Product.objects.all()
    )

    class Meta:
        """Define the fields for all ReturnItems operations."""

        model = ReturnItems
        fields = "__all__"


class PendingItemSerializer(serializers.ModelSerializer):
    """Define response properties for PendingItems requests."""

    class Meta:
        """Define return fields for all PendingItems requests."""

        model = PendingItems
        fields = "__all__"


class ReadOrderTypeSerializer(OrderTypeSerializer):
    """Define response properties for types of Order."""

    source = serializers.CharField(source="order.source.name")
    courier = serializers.CharField(source="order.courier")
    tracking_id = serializers.CharField(source="order.tracking_id")
    name = serializers.CharField(source="order.name")
    contact = serializers.CharField(source="order.contact")


class ReadRefundSerializer(ReadOrderTypeSerializer):
    """
    Define response properties for Refund read based requests.

    Inherit properties defined in RefundSerializer and ReadOrderTypeSerializer.
    """

    class Meta(ReadOrderTypeSerializer.Meta):
        """
        Define return fields for all read based Refund requests.

        Inherits extra_fields from ReadOrderTypeSerializer.
        """

        model = Refund
        extra_fields = ReadOrderTypeSerializer.Meta.fields


class ReadResendSerializer(ReadOrderTypeSerializer, ResendSerializer):
    """
    Define response properites for Resend read based requests.

    Inherit properties defined in ResendSerializer and ReadOrderTypeSerializer.
    """

    class Meta:
        """
        Define return fields for all reading Resend orders.

        Inherits extra_fields from ReadOrderTypeSerializer.
        """

        model = Resend
        fields = (
            "order",
            "source",
            "courier",
            "tracking_id",
            "name",
            "contact",
            "reason",
            "option",
            "room",
            "picker",
            "packer",
            "created",
            "notes",
            "dor",
            "user",
        )
        extra_fields = ReadOrderTypeSerializer.Meta.fields


class ReadPendingSerializer(PendingSerializer, ReadOrderTypeSerializer):
    """
    Define response properties for Pending read based requests.

    Inherit properties defined in PendingSerializer and
    ReadOrderTypeSerializer.
    """

    courier = serializers.SlugRelatedField(
        slug_field="name", queryset=Courier.objects.all()
    )

    items = serializers.CharField(
        source="get_pending_items", required=False, allow_blank=True
    )

    name = serializers.CharField(
        source="original_order.name", required=False, allow_blank=True
    )

    class Meta:
        """
        Define the fields to be returned when reading Pending orders.

        Inherits extra_fields from from ReadOrderTypeSerializer.
        """

        model = Pending
        fields = (
            "order",
            "original_order",
            "name",
            "courier",
            "created",
            "reason",
            "option",
            "notes",
            "room",
            "picker",
            "packer",
            "dor",
            "user",
            "items",
        )


class ReadReturnSerializer(ReturnSerializer, ReadOrderTypeSerializer):
    """
    Define response properties for Return read based requests.

    Inherit properties defined in ReturnSerializer and ReadOrderTypeSerializer.
    """

    items = serializers.CharField(
        source="get_return_items", required=False, allow_blank=True
    )

    class Meta:
        """
        Define the fields to be returned when reading Return orders.

        Inherits extra_fields from from ReadOrderTypeSerializer.
        """

        model = Return
        fields = (
            "order",
            "source",
            "courier",
            "tracking_id",
            "name",
            "contact",
            "reason",
            "option",
            "created",
            "action_customer",
            "notes",
            "user",
            "items",
        )
        extra_fields = ReadOrderTypeSerializer.Meta.fields


class OrderDateTotalSerializer(serializers.Serializer):
    """
    Define response properties for OrderMixin responses.

    OrderMethodMixin get_order_count method for ordertypes.
    """

    order_total = serializers.IntegerField(required=False)
    amount_total = serializers.FloatField(required=False)
    created = serializers.CharField(required=False, allow_blank=True)
    reason = serializers.CharField(
        source="reason__name", required=False, allow_blank=True
    )
    action = serializers.CharField(
        source="action__name", required=False, allow_blank=True
    )
    warehouse = serializers.CharField(
        source="warehouse__name", required=False, allow_blank=True
    )
    room = serializers.CharField(source="room__name", required=False, allow_blank=True)


class LookupOptionSerializer(serializers.Serializer):
    source = serializers.ListField(child=serializers.CharField(), required=False)
    reason = serializers.ListField(child=serializers.CharField(), required=False)
    option = serializers.ListField(child=serializers.CharField(), required=False)
    room = serializers.ListField(child=serializers.CharField(), required=False)
    warehouse = serializers.ListField(child=serializers.CharField(), required=False)
    picker = serializers.ListField(child=serializers.CharField(), required=False)
    packer = serializers.ListField(child=serializers.CharField(), required=False)
    action_customer = serializers.ListField(
        child=serializers.CharField(), required=False
    )
    courier = serializers.ListField(child=serializers.CharField(), required=False)


class ClaimsSerializer(serializers.Serializer):
    """Serilaizer for claimable records."""

    order = serializers.CharField(source="order_id")
    type = serializers.CharField(required=False)
    courier = serializers.CharField(source="order.courier")
    reason = serializers.CharField(required=False)
    created = serializers.CharField(required=False)
    user = serializers.CharField(required=False)
