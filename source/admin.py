"""Imports for source admin."""
from django.contrib import admin
from . import models

admin.site.register(models.Source)
