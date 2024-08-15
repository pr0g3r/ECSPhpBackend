"""Imports for Order Mixins."""
from .serializers import (
    OrderDateTotalSerializer,
)
from rest_framework.response import Response
from rest_framework.decorators import action
from django.db.models import Count


class OrderMixin:
    """Mixins for child of Order."""

    @action(
        detail=False,
        url_path=(
            r'get_order_count/'
            '(?P<start>\\d{4}-\\d{2}-\\d{2})/'
            '(?P<end>\\d{4}-\\d{2}-\\d{2})'
        )
    )
    def get_order_count(self, *args, **kwargs):
        """Order count for child of order by day, within a given range."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .values("created")
            .annotate(order_total=Count("order_id"))
            .order_by("created")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)

    @action(
        detail=False,
        url_path=(
            r'get_reason_count/'
            '(?P<start>\\d{4}-\\d{2}-\\d{2})/'
            '(?P<end>\\d{4}-\\d{2}-\\d{2})'
        )
    )
    def get_reason_count(self, *args, **kwargs):
        """Order count for child of order per reason, within a given range."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset().filter(created__range=[start, end])
            .values("reason__name")
            .annotate(order_total=Count("order_id"))
            .order_by("reason")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)

    @action(
        detail=False,
        url_path=(
            r'get_reason_daily/'
            '(?P<start>\\d{4}-\\d{2}-\\d{2})/'
            '(?P<end>\\d{4}-\\d{2}-\\d{2})'
        )
    )
    def get_reason_daily(self, *args, **kwargs):
        """Order count for reason per day in range for child of order."""
        start = kwargs["start"]
        end = kwargs["end"]
        qs = (
            self.get_queryset()
            .filter(created__range=[start, end])
            .values("created", "reason__name")
            .annotate(order_total=Count("order_id"))
            .order_by("created")
        )
        serializer = OrderDateTotalSerializer(qs, many=True)
        return Response(serializer.data)
