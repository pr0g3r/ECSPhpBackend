"""Imports for Courier views."""
from .serializers import CourierSerializer
from .models import Courier
from rest_framework.viewsets import ModelViewSet
from django_filters.rest_framework import DjangoFilterBackend


class CourierModelViewSet(ModelViewSet):
    queryset = Courier.objects.all()
    serializer_class = CourierSerializer
    filter_backends = (
        DjangoFilterBackend,
    )
    filterset_fields = ("claimable", )
