# Generated by Django 3.2.7 on 2022-02-16 12:11

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0033_claimsforms_claimsordertype_claimsrefund_claimsresend'),
    ]

    operations = [
        migrations.AddField(
            model_name='option',
            name='claimable',
            field=models.BooleanField(default=False),
        ),
    ]
