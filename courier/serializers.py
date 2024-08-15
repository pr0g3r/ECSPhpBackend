"""Import for Courier Serializers."""
from .models import Courier
from rest_framework import serializers


class CourierSerializer(serializers.ModelSerializer):
    class Meta:
        model = Courier
        fields = ["name"]
