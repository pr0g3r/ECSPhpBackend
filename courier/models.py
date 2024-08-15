"""Imports for Courier models."""
from django.db import models


class Courier(models.Model):
    """Table schema for Courier, defines attributes of a Courier."""

    name = models.CharField(max_length=(50), unique=True)
    claimable = models.BooleanField(default=False)

    def __str__(self):
        """Define return value."""
        return self.name
