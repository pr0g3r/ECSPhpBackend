"""Imports for Order filters."""
import django_filters
from .models import (
    Refund,
    Resend,
    Pending,
    Return,
)


class OrderTypeFilter(django_filters.FilterSet):
    """
    Parent filter class for all types of Order.

    Defines filter fields and ordering fields that are common between types
    of Order.
    """

    # Define CharFilters using foreign key relationship on Order.
    source = django_filters.CharFilter(
        field_name="order__source__name", lookup_expr="iexact"
    )
    courier = django_filters.CharFilter(
        field_name="order__courier__name", lookup_expr="iexact"
    )

    # Define ChartFilters for direct relation to tables.
    reason = django_filters.CharFilter(
        field_name="reason__name", lookup_expr="iexact"
    )
    option = django_filters.CharFilter(
        field_name="option__name", lookup_expr="iexact"
    )

    class Meta:
        """Fields that will be included in the filterset_fields."""

        fields = (
            "source",
            "reason",
        )


class RefundFilter(OrderTypeFilter):
    """
    Child of OrderTypeFilter for filtering options related to Refunds.

    Inherits properties defined in OrderTypeFilter.
    """

    # Refund fields that can be ordered.
    ordering = django_filters.OrderingFilter(
        fields=(
            "created",
            "amount",
            "full_refund",
            "void_order",
            "dor",
        )
    )

    class Meta(OrderTypeFilter.Meta):
        """
        Define model for filer class.

        Inherits fields defined in OrderTypeFilter.Meta.
        """

        model = Refund


class ResendFilter(OrderTypeFilter):
    """
    Child of OrderTypeFilter for filtering options related to Resends.

    Inherits properties defined in OrderTypeFilter.
    """

    # Define CharFilters for fields from Resend.
    room = django_filters.CharFilter(
        field_name="room__name", lookup_expr="iexact"
    )
    picker = django_filters.CharFilter(
        field_name="picker__name", lookup_expr="iexact"
    )
    packer = django_filters.CharFilter(
        field_name="packer__name", lookup_expr="iexact"
    )

    ordering = django_filters.OrderingFilter(
        fields=(
            "created",
            "dor",
        )
    )

    class Meta(OrderTypeFilter.Meta):
        """
        Define model for filer class.

        Add additional filers fields unique to Resends, inherit fields defined
        in OrderTypeFilter.Meta.
        """

        model = Resend
        fields = (
            "room",
            "picker",
            "packer",
        )


class PendingFilter(ResendFilter):
    """
    Child of OrderTypeFilter for filtering options related to Pending.

    Inherits properties defined in ResendFilter.
    """

    # Override default courier field mapping defined in OrderTypeFilter,
    # iherited from ResendFilter, instead map directly to the courier table
    # as Pending has no dependency on Order.
    courier = django_filters.CharFilter(
        field_name="courier__name", lookup_expr="iexact"
    )

    class Meta(ResendFilter.Meta):
        """
        Define model for filer class.

        Inherits fields defined in ResendFilter.Meta.
        """

        model = Pending


class ReturnFilter(OrderTypeFilter):
    """
    Child of OrderTypeFilter for filtering options related to Returns.

    Inherits properties defined in OrderTypeFilter.
    """

    ordering = django_filters.OrderingFilter(
        fields=(
            "created",
        )
    )

    action_customer = django_filters.CharFilter(
        field_name="action_customer__name", lookup_expr="iexact"
    )

    class Meta(OrderTypeFilter.Meta):
        """
        Define model for filer class.

        Add additional filers fields unique to Returns, inherit fields defined
        in OrderTypeFilter.Meta.
        """

        model = Return
        fields = (
            "action_customer",
        )
