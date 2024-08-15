"""Imports for Order paginators."""
from rest_framework import pagination
from rest_framework.response import Response


class OrderPagination(pagination.PageNumberPagination):
    """Pagination class to define the format and amount of results to return per page."""

    def get_paginated_response(self, data):
        """Content of formatted response."""
        return Response(
            {
                "links": {
                    "next": self.get_next_link(),
                    "previous": self.get_previous_link(),
                },
                "count": self.page.paginator.count,
                "total_pages": self.page.paginator.num_pages,
                "current_page": self.page.number,
                "results": data,
            }
        )
