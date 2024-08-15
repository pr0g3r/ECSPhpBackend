"""Imports for Courier admin."""
from django.contrib import admin
from . import models

admin.site.register(models.Courier)
