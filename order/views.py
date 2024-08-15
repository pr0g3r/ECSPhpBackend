"""Imports for Order views."""
from .serializers import (
    Action_Customer_Serializer,
    Action_Product_Serializer,
    ReasonSerializer,
    OptionSerializer,
    RoomSerializer,
    WarehouseSerializer,
    OrderSerializer,
    RefundSerializer,
    ReadRefundSerializer,
    ResendSerializer,
    ReadResendSerializer,
    PendingSerializer,
    ReadPendingSerializer,
    ReturnSerializer,
    ReadReturnSerializer,
    OrderDateTotalSerializer,
    ReturnItemSerializer,
    PendingItemSerializer,
    LookupOptionSerializer,
    ClaimsSerializer,
    ClaimFormsSerializer,
    ReadClaimFormsSerializer,
)
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
from courier.models import Courier
from source.models import Source
from django.contrib.auth.models import User
from django.db.models import CharField, Value
from .mixins import OrderMixin
from rest_framework.filters import SearchFilter, OrderingFilter
from django_filters.rest_framework import DjangoFilterBackend
from rest_framework.viewsets import ModelViewSet
from rest_framework import status
from rest_framework.response import Response
from rest_framework.decorators import action
from django.db.models import Count, Sum
from django.db.models import F
from itertools import chain
from . import filters


class Action_Customer_ModelViewSet(ModelViewSet):
    queryset = Action_Customer.objects.all()
    serializer_class = Action_Customer_Serializer


class Action_Product_ModelViewSet(ModelViewSet):
    queryset = Action_Product.objects.all()
    serializer_class = Action_Product_Serializer


class ReasonModelViewSet(ModelViewSet):
    queryset = Reason.objects.all()
    serializer_class = ReasonSerializer


class OptionModelViewSet(ModelViewSet):
    queryset = Option.objects.all()
    serializer_class = OptionSerializer
    filter_backends = (DjangoFilterBackend,)
    filterset_fields = ("claimable",)


class RoomModelViewSet(ModelViewSet):
    queryset = Room.objects.all()
    serializer_class = RoomSerializer


class WarehouseModelViewSet(ModelViewSet):
    queryset = Warehouse.objects.all()
    serializer_class = WarehouseSerializer
    filter_backends = (
        SearchFilter,
        OrderingFilter,
        DjangoFilterBackend,
    )
    filterset_fields = ("active",)


class ClaimsModelViewSet(ModelViewSet):
    queryset = Claims.objects.all()
    serializer_class = ClaimsSerializer

    @action(detail=False, methods=["post"])
    def claimable(self, request):
        """
        Retrieve a list of valid resends for the claims view. If filter
        arguments are passed we will show all claimable records.
        """
        # Collect already claimed orders
        claimed = self.get_queryset().values_list("order_id", flat=True)

        # Build map to use in queryset filtering
        conditions = dict()
        if "courier" in request.data and request.data["courier"]:
            conditions["order__courier__name"] = request.data["courier"]
        else:
            conditions["order__courier__in"] = Courier.objects.filter(claimable=1)

        if "option" in request.data and request.data["option"]:
            conditions["option__name"] = request.data["option"]
        else:
            conditions["option__in"] = Option.objects.filter(claimable=1)

        # Retrieve records which are claimable
        validResends = (
            Resend.objects.exclude(order__order_id__in=claimed)
            .annotate(type=Value("Resend", output_field=CharField()))
            .filter(**conditions)
            .order_by("created")
        )
        validRefunds = (
            Refund.objects.exclude(order__order_id__in=claimed)
            .annotate(type=Value("Refund", output_field=CharField()))
            .filter(**conditions)
            .order_by("created")
        )
        # Combine the results from each model
        valid = chain(validResends, validRefunds)
        serializer = self.get_serializer(valid, many=True)
        return Response(serializer.data)

    @action(detail=False, methods=["post"])
    def form_orders(self, request, *args, **kwargs):
        orders = Order.objects.filter(order_id__in=request.data["ids"])
        form = ClaimForms.objects.get(id=request.data["form"])

        order_totals = request.data["totals"]

        for order in orders:
            Claims.objects.create(order=order, total=order_totals[order.order_id], form=form)

        return Response(request.data["ids"], status=status.HTTP_201_CREATED)

    @action(detail=False, methods=["post"])
    def form_response(self, request, *args, **kwargs):
        claim_response = request.data["response"]
        records = Claims.objects.filter(order__order_id__in=request.data["ids"], form=request.data["form"])
        claim_form = ClaimForms.objects.filter(reference=request.data["reference"]).first()

        response = []
        for record in records:
            record_response = claim_response[record.order_id]
            record.status = record_response["status"]
            if record_response["status"]:
                record.accepted_total = record_response["accepted_total"]

            record.save()
            response.append(record_response)

        if claim_form.actual_payout is None:
            claim_form.actual_payout = 0

        claim_form.actual_payout += float(request.data["actual_payout"])
        complete = len([rec.order for rec in records if rec.status])
        if complete == claim_form.claim_count:
            claim_form.status = True
        else:
            claim_form.status = False

        claim_form.save()
        return Response(response, status=status.HTTP_200_OK)

    @action(
        detail=False,
        url_path=(
            r"disputed_claims/"
            "(?P<form>[^/.]+)"
        ),
    )
    def disputed_claims(self, *args, **kwargs):
        form_disputed = self.get_queryset().filter(form=kwargs["form"], status=False)
        serializer = self.get_serializer(form_disputed, many=True)
        return Response(serializer.data)


class ClaimFormsModelViewSet(ModelViewSet):
    queryset = ClaimForms.objects.all()
    serializer_class = ClaimFormsSerializer
    filter_backends = (
        SearchFilter,
        OrderingFilter,
        DjangoFilterBackend,
    )
    ordering_fields = ("created",)

    def get_serializer_class(self):
        """Set serializer based on http request action."""
        if self.action in ("list", "retrieve"):
            return ReadClaimFormsSerializer
        return ClaimFormsSerializer


class OrderModelViewSet(ModelViewSet):
    queryset = Order.objects.select_related(
        "source",
        "courier",
    )
    serializer_class = OrderSerializer
    filter_backends = (
        SearchFilter,
        OrderingFilter,
        DjangoFilterBackend,
    )
    search_fields = (
        "order_id",
        "tracking_id",
        "name",
        "contact",
    )
    filterset_fields = (
        "source__name",
        "courier__name",
    )

    @action(
        detail=False,
        url_path=(r"order_form_options/" "(?P<lookups>.+)"),
    )
    def order_form_options(self, *args, **kwargs):
        """Retrieve lookup options for frontend form."""
        lookups = kwargs["lookups"].split("/")
        results = {}
        for model in lookups:
            if model == "picker" or model == "packer":
                qs = Warehouse.objects.all().values_list("name", flat=True)
            else:
                qs = (
                    globals()[model.title()]
                    .objects.all()
                    .values_list("name", flat=True)
                )

            if model == "warehouse" or model == "picker" or model == "packer":
                qs = qs.filter(active=1)

            results[model] = qs

        serializer = LookupOptionSerializer(results)
        return Response(serializer.data)


class RefundModelViewSet(ModelViewSet, OrderMixin):
    queryset = Refund.objects.select_related(
        "order",
        "reason",
        "option",
        "user",
    )
    filter_backends = (
        SearchFilter,
        DjangoFilterBackend,
    )
    filter_class = filters.RefundFilter
    search_fields = (
        "order__order_id",
        "order__tracking_id",
        "order__name",
        "created",
    )

    def get_serializer_class(self):
        """Set serializer based on http request action."""
        if self.action in ("list", "retrieve"):
            return ReadRefundSerializer
        return RefundSerializer

    @action(
        detail=False,
        url_path=(
            r"get_amount_daily/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def get_amount_daily(self, *args, **kwargs):
        """Total up the total amount refunded for dates in range."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .values("created")
            .annotate(amount_total=Sum("amount"))
            .order_by("created")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)

    @action(
        detail=False,
        url_path=(
            r"get_void_daily/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def get_void_daily(self, *args, **kwargs):
        """Count of voided orders by day."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end], void_order=False)
            .values("created")
            .annotate(order_total=Count("order_id"))
            .order_by("created")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)


class ResendModelViewSet(ModelViewSet, OrderMixin):
    queryset = Resend.objects.select_related(
        "order",
        "reason",
        "room",
        "picker",
        "packer",
        "user",
    )
    filter_backends = (
        SearchFilter,
        DjangoFilterBackend,
    )
    filter_class = filters.ResendFilter
    search_fields = (
        "order__order_id",
        "order__tracking_id",
        "order__name",
        "created",
    )

    def get_serializer_class(self):
        """Set serializer based on http request action."""
        if self.action in ("list", "retrieve"):
            return ReadResendSerializer
        return ResendSerializer

    @action(
        detail=False,
        url_path=(
            r"get_warehouse_count/"
            "(?P<type>picker|packer)/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def get_warehouse_count(self, *args, **kwargs):
        """Retrieve the count of resend orders picked or packed by warehouse staff."""
        start = kwargs["start"]
        end = kwargs["end"]
        type = kwargs["type"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .annotate(warehouse__name=F(f"{type}__name"))
            .values("warehouse__name")
            .annotate(order_total=Count("order_id"))
            .order_by("warehouse__name")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)

    @action(
        detail=False,
        url_path=(
            r"get_room_count/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def get_room_count(self, *args, **kwargs):
        """Retrieve the count of resend orders by room."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .values("room__name")
            .annotate(order_total=Count("order_id"))
            .order_by("room__name")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)


class PendingModelViewSet(ModelViewSet, OrderMixin):
    queryset = Pending.objects.select_related(
        "reason",
        "room",
        "picker",
        "packer",
        "user",
    )
    filter_backends = (
        SearchFilter,
        DjangoFilterBackend,
    )
    filter_class = filters.PendingFilter
    search_fields = (
        "order",
        "created",
    )

    def get_serializer_class(self):
        """Set serializer based on http request action."""
        if self.action in ("list", "retrieve"):
            return ReadPendingSerializer
        return PendingSerializer


class ReturnModelViewSet(ModelViewSet, OrderMixin):
    queryset = Return.objects.select_related(
        "order",
        "reason",
        "option",
        "action_customer",
        "user",
    )
    filter_backends = (
        SearchFilter,
        DjangoFilterBackend,
    )
    filter_class = filters.ReturnFilter
    search_fields = (
        "order__order_id",
        "order__tracking_id",
        "order__name",
        "created",
    )

    def get_serializer_class(self):
        """Set serializer based on http request action."""
        if self.action in ("list", "retrieve"):
            return ReadReturnSerializer
        return ReturnSerializer

    @action(
        detail=False,
        url_path=(
            r"action_customer_count/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def action_customer_count(self, *args, **kwargs):
        """Order count for Return per action, within a given range."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .annotate(action__name=F("action_customer__name"))
            .values("action__name")
            .annotate(order_total=Count("order_id"))
            .order_by("action__name")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)

    @action(
        detail=False,
        url_path=(
            r"action_product_count/"
            "(?P<start>\\d{4}-\\d{2}-\\d{2})/"
            "(?P<end>\\d{4}-\\d{2}-\\d{2})"
        ),
    )
    def action_product_count(self, *args, **kwargs):
        """Count of each action type for Return orders in a given range."""
        start = kwargs["start"]
        end = kwargs["end"]
        orders = self.get_queryset().filter(created__range=[start, end])
        qs = (
            ReturnItems.objects.filter(order__in=orders)
            .annotate(action__name=F("action_product__name"))
            .values("action__name")
            .annotate(order_total=Count("order_id"))
            .order_by("action__name")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)


class ItemModelViewSet(ModelViewSet):
    filter_backends = (
        SearchFilter,
        DjangoFilterBackend,
    )

    def retrieve(self, request, *args, **kwargs):
        """Override default retrieve behavior to select specific sku."""
        qs = self.get_queryset().filter(order=kwargs["pk"])
        if "sku" in request.GET:
            qs = qs.filter(sku=request.GET["sku"])
        serializer = self.get_serializer(qs, many=True)
        return Response(serializer.data)


class ReturnItemsModelViewSet(ItemModelViewSet):
    serializer_class = ReturnItemSerializer
    queryset = ReturnItems.objects.select_related(
        "order",
        "action_product",
    )
    search_fields = ("order__order_id",)


class PendingItemsModelViewSet(ItemModelViewSet):
    serializer_class = PendingItemSerializer
    queryset = PendingItems.objects.select_related(
        "order",
    )
    search_fields = ("order__order_id",)
