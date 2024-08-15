"""Imports for Order models."""
from django.db import models
from source.models import Source
from courier.models import Courier
from django.contrib.auth.models import User


class Action_Customer(models.Model):
    """
    Table schema for Customer Actions.

    Customer actions that can be taken on a Return Order.
    """

    name = models.CharField(max_length=(100), unique=True)

    def __str__(self):
        """Define return value."""
        return self.name


class Action_Product(models.Model):
    """
    Table schema for Product Action.

    Actions that can be taken on the Products of a Return Order.
    """

    name = models.CharField(max_length=(100), unique=True)

    def __str__(self):
        """Define return value."""
        return self.name


class Reason(models.Model):
    """
    Table schema Reason.

    Reason for order being Refund / Resend / Return.
    """

    name = models.CharField(max_length=(100), unique=True)

    def __str__(self):
        """Define return value."""
        return self.name


class Option(models.Model):
    """
    Table schema for Return Option.

    All possible options that can be taken on an Order,
    primarily used by Returns.
    """

    name = models.CharField(max_length=(100), unique=True)
    claimable = models.BooleanField(default=False)

    def __str__(self):
        """Define return value."""
        return self.name


class Room(models.Model):
    """
    Table schema for Room.

    Contains each room in the warehouse.
    """

    name = models.CharField(max_length=(50), unique=True)

    def __str__(self):
        """Define return value."""
        return self.name


class Warehouse(models.Model):
    """
    Table schema for Warehouse.

    List workers in the warehouse.
    """

    name = models.CharField(max_length=(75), unique=True)
    active = models.BooleanField()

    def __str__(self):
        """Define return value."""
        return self.name


class Order(models.Model):
    """
    Table schema for Order.

    Contains all the order specific information that will be constant across
    the types of order.
    """

    order_id = models.CharField(primary_key=True, max_length=(50))
    source = models.ForeignKey(Source, on_delete=models.CASCADE)
    courier = models.ForeignKey(Courier, on_delete=models.CASCADE)
    tracking_id = models.CharField(max_length=(50), null=True)
    name = models.CharField(max_length=(50), null=True)
    contact = models.CharField(max_length=(100), null=True)


class Refund(models.Model):
    """
    Table schema for Refund.

    Contains fields specific to a Refund record, one to one dependency on Order
    table.
    """

    order = models.OneToOneField(Order, primary_key=True, on_delete=models.CASCADE)
    created = models.DateField()
    reason = models.ForeignKey(Reason, on_delete=models.CASCADE)
    option = models.ForeignKey(Option, on_delete=models.CASCADE)
    order_total = models.FloatField()
    notes = models.CharField(max_length=(255), null=True)
    full_refund = models.BooleanField()
    amount = models.FloatField()
    void_order = models.BooleanField()
    dor = models.BooleanField()
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)


class Resend(models.Model):
    """
    Table schema for Resend.

    Contains fields specific to a Resend record, one to one dependency
    on Order table.
    """

    order = models.OneToOneField(Order, primary_key=True, on_delete=models.CASCADE)
    created = models.DateField()
    reason = models.ForeignKey(Reason, on_delete=models.CASCADE)
    option = models.ForeignKey(Option, on_delete=models.CASCADE)
    notes = models.CharField(max_length=(255), null=True)
    room = models.ForeignKey(Room, on_delete=models.SET_NULL, null=True)
    picker = models.ForeignKey(Warehouse, on_delete=models.SET_NULL, null=True)
    packer = models.ForeignKey(
        Warehouse, related_name="ResendPacker", on_delete=models.SET_NULL, null=True
    )
    dor = models.BooleanField()
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)


class Pending(models.Model):
    """
    Table schema for Pending.

    Contains fields specific to a Pending record.
    """

    order = models.CharField(primary_key=True, max_length=(50))
    original_order = models.ForeignKey(Order, on_delete=models.SET_NULL, null=True)
    courier = models.ForeignKey(Courier, on_delete=models.CASCADE)
    created = models.DateField()
    reason = models.ForeignKey(Reason, on_delete=models.CASCADE)
    option = models.ForeignKey(Option, on_delete=models.CASCADE)
    notes = models.CharField(max_length=(255), null=True)
    room = models.ForeignKey(Room, on_delete=models.SET_NULL, null=True)
    picker = models.ForeignKey(
        Warehouse, related_name="PendingPicker", on_delete=models.SET_NULL, null=True
    )
    packer = models.ForeignKey(
        Warehouse, related_name="PendingPacker", on_delete=models.SET_NULL, null=True
    )
    dor = models.BooleanField()
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)

    def get_pending_items(self):
        items = PendingItems.objects.filter(order=self)
        item_str = ""

        for item in items:
            item_str += f"{item.qty} x {item.title} | "

        return item_str.rsplit("|", 1)[0]

    get_pending_items.short_description = "items"


class PendingItems(models.Model):
    """
    Table schema for Pending Items.

    Stores the items entered by the user when creating resend orders.
    """

    order = models.ForeignKey(Pending, on_delete=models.CASCADE)
    sku = models.CharField(max_length=255)
    title = models.CharField(max_length=(255))
    qty = models.IntegerField()
    shipping = models.FloatField()
    price = models.FloatField()

    class Meta:
        """Define unique pair value to prevent duplication items."""

        unique_together = (
            "order",
            "sku",
        )


class Return(models.Model):
    """
    Table schema for Return.

    Contains fields specific to a Return record, one to one dependency
    on Order table.
    """

    order = models.OneToOneField(Order, primary_key=True, on_delete=models.CASCADE)
    created = models.DateField()
    notes = models.CharField(max_length=(255), null=True)
    reason = models.ForeignKey(Reason, on_delete=models.CASCADE)
    option = models.ForeignKey(Option, on_delete=models.CASCADE)
    action_customer = models.ForeignKey(Action_Customer, on_delete=models.CASCADE)
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)

    def get_return_items(self):
        items = ReturnItems.objects.filter(order=self)
        item_str = ""

        for item in items:
            item_str += f"{item.qty} x {item.title} | "

        return item_str.rsplit("|", 1)[0]

    get_return_items.short_description = "items"


class ReturnItems(models.Model):
    """
    Table scheme for Return Items.

    Contains a list of items and the product options that were applied to them.
    The actionQty field here notes how much of the original qty
    was effected by the action.
    """

    order = models.ForeignKey(Return, on_delete=models.CASCADE)
    sku = models.CharField(max_length=(255))
    title = models.CharField(max_length=(255))
    shipping = models.FloatField()
    price = models.FloatField()
    qty = models.IntegerField()
    action_qty = models.IntegerField()
    action_product = models.ForeignKey(
        Action_Product, on_delete=models.SET_NULL, null=True
    )

    class Meta:
        """Define unique pair value to prevent duplicate items."""

        unique_together = (
            "order",
            "sku",
        )


class ClaimForms(models.Model):
    """
    Contains all common fields for types of claim forms.

    Status:
    Null: Pending
    False: Challenged (Courier has challanged claim)
    True: Complete
    """
    reference = models.CharField(max_length=100, unique=True)
    courier = models.ForeignKey(Courier, on_delete=models.SET_NULL, null=True)
    claim_count = models.IntegerField()
    expected_payout = models.FloatField()
    actual_payout = models.FloatField(null=True)
    status = models.BooleanField(null=True)
    created = models.DateField()
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)


class Claims(models.Model):
    """
    Contains all common fields for types of claimable order.

    Use of nullable boolean field to indicate 3 states:
    Null: Pending
    False: Challenged (Courier has challanged claim)
    True: Complete
    """

    order = models.ForeignKey(Order, on_delete=models.CASCADE)
    total = models.FloatField()
    accepted_total = models.FloatField(null=True)
    status = models.BooleanField(null=True)
    rejected = models.BooleanField(default=False)
    form = models.ForeignKey(ClaimForms, null=True, on_delete=models.SET_NULL)
