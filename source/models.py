"""Imports for source models."""
from django.db import models


class Source(models.Model):
    """Table schema for Orders Source."""

    name = models.CharField(max_length=(100), unique=True)

    def __str__(self):
        """Define return value."""
        return self.name
