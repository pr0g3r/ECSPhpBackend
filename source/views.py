"""Imports for source views."""
from .serializers import SourceSerializer
from .models import Source
from rest_framework.viewsets import ModelViewSet


class SourceModelViewSet(ModelViewSet):
    """Set of handelers for source requests."""

    queryset = Source.objects.all()
    serializer_class = SourceSerializer
